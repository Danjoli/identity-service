<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\AccountDisabled;
use App\Exception\EmailAlreadyExists;
use App\Exception\InvalidCredentials;
use App\Exception\InvalidCurrentPassword;
use App\Exception\InvalidEmailVerificationToken;
use App\Exception\InvalidPasswordResetToken;
use App\Exception\InvalidRefreshToken;
use App\Exception\SelfAuthorizationChange;
use App\Exception\UserNotFound;
use App\Observability\RequestContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiProblemDetailsSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 100],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        $exception = $event->getThrowable();
        $validation = $this->validationException($exception);
        if (null !== $validation) {
            $this->respond($event, 422, 'validation_failed', 'Validation failed.', $this->violations($validation));

            return;
        }

        if ($exception instanceof UnsupportedMediaTypeHttpException) {
            $this->respond($event, 415, 'unsupported_media_type', 'The request content type is not supported.');

            return;
        }

        if ($this->hasCause($exception, \JsonException::class)
            || $this->hasCause($exception, NotEncodableValueException::class)) {
            $this->respond($event, 400, 'malformed_json', 'The request body contains malformed JSON.');

            return;
        }

        if ($exception instanceof AccessDeniedException) {
            if (null === $this->security->getUser()) {
                $this->respond($event, 401, 'authentication_required', 'Authentication is required.');
            } else {
                $this->respond($event, 403, 'access_denied', 'You do not have permission to perform this operation.');
            }

            return;
        }

        $mapping = match (true) {
            $exception instanceof AuthenticationException => [401, 'authentication_required'],
            $exception instanceof EmailAlreadyExists => [409, 'email_already_exists'],
            $exception instanceof InvalidCredentials => [401, 'invalid_credentials'],
            $exception instanceof AccountDisabled => [403, 'account_disabled'],
            $exception instanceof InvalidRefreshToken => [401, 'invalid_refresh_token'],
            $exception instanceof SelfAuthorizationChange => [409, 'self_authorization_change_forbidden'],
            $exception instanceof InvalidCurrentPassword => [401, 'invalid_current_password'],
            $exception instanceof InvalidEmailVerificationToken => [400, 'invalid_email_verification_token'],
            $exception instanceof InvalidPasswordResetToken => [400, 'invalid_password_reset_token'],
            $exception instanceof UserNotFound => [404, 'user_not_found'],
            default => null,
        };

        if (is_array($mapping)) {
            $this->respond($event, $mapping[0], $mapping[1], $exception->getMessage());

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $detail = 429 === $status ? 'Too many requests. Try again later.' : Response::$statusTexts[$status] ?? 'Request failed.';
            $this->respond(
                $event,
                $status,
                $this->httpCode($status),
                $detail,
                headers: $this->responseHeaders($exception->getHeaders()),
            );

            return;
        }

        $this->respond($event, 500, 'internal_error', 'An unexpected error occurred.');
    }

    /**
     * @param array<string, list<string>>|null   $violations
     * @param array<string, string|list<string>> $headers
     */
    private function respond(
        ExceptionEvent $event,
        int $status,
        string $code,
        string $detail,
        ?array $violations = null,
        array $headers = [],
    ): void {
        $request = $event->getRequest();
        $problem = [
            'type' => 'urn:identity-service:problem:'.$code,
            'title' => Response::$statusTexts[$status] ?? 'Request failed',
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getRequestUri(),
            'code' => $code,
            'requestId' => $this->requestId($request),
        ];
        if (null !== $violations) {
            $problem['violations'] = $violations;
        }

        $event->setResponse(new JsonResponse(
            $problem,
            $status,
            [...$headers, 'Content-Type' => 'application/problem+json', 'X-Request-ID' => $this->requestId($request)],
        ));
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }

    private function requestId(Request $request): string
    {
        $requestId = $request->attributes->get(RequestContext::REQUEST_ID);

        return is_string($requestId) ? $requestId : '';
    }

    private function validationException(\Throwable $exception): ?ValidationFailedException
    {
        do {
            if ($exception instanceof ValidationFailedException) {
                return $exception;
            }
            $exception = $exception->getPrevious();
        } while (null !== $exception);

        return null;
    }

    /** @return array<string, list<string>> */
    private function violations(ValidationFailedException $exception): array
    {
        $violations = [];
        foreach ($exception->getViolations() as $violation) {
            $field = '' !== $violation->getPropertyPath() ? $violation->getPropertyPath() : 'body';
            $violations[$field][] = sprintf('%s', $violation->getMessage());
        }

        return $violations;
    }

    /** @param class-string<\Throwable> $class */
    private function hasCause(\Throwable $exception, string $class): bool
    {
        do {
            if ($exception instanceof $class) {
                return true;
            }
            $exception = $exception->getPrevious();
        } while (null !== $exception);

        return false;
    }

    private function httpCode(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'authentication_required',
            403 => 'access_denied',
            404 => 'not_found',
            405 => 'method_not_allowed',
            415 => 'unsupported_media_type',
            422 => 'validation_failed',
            429 => 'rate_limit_exceeded',
            default => 'http_error',
        };
    }

    /**
     * @param array<mixed, mixed> $headers
     *
     * @return array<string, string|list<string>>
     */
    private function responseHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            if (is_string($value)) {
                $normalized[$name] = $value;

                continue;
            }

            if (is_int($value) || is_float($value)) {
                $normalized[$name] = (string) $value;

                continue;
            }

            if (is_array($value) && array_is_list($value)) {
                $textValues = array_filter($value, is_string(...));
                if (count($textValues) === count($value)) {
                    $normalized[$name] = array_values($textValues);
                }
            }
        }

        return $normalized;
    }
}
