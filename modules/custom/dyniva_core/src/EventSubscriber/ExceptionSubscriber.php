<?php

namespace Drupal\dyniva_core\EventSubscriber;

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Utility\Error;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\EventSubscriber\FinalExceptionSubscriber;

/**
 * Custom exception subscriber.
 */
class ExceptionSubscriber extends FinalExceptionSubscriber implements EventSubscriberInterface {

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionally in update.php.
    $message = '';
    if ($this->isErrorDisplayable($error)) {
      // If error type is 'User notice' then treat it as debug information
      // instead of an error message.
      // @see debug()
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
      }

      $error = $this->simplifyFileInError($error);

      unset($error['backtrace']);

      if (!$this->isErrorLevelVerbose()) {
        // Without verbose logging, use a simple message.
        // We call SafeMarkup::format directly here, rather than use t() since
        // we are in the middle of error handling, and we don't want t() to
        // cause further errors.
        $message = SafeMarkup::format('%type: @message in %function (line %line of %file).', $error);
      }
      else {
        // With verbose logging, we will also include a backtrace.
        $backtrace_exception = $exception;
        while ($backtrace_exception->getPrevious()) {
          $backtrace_exception = $backtrace_exception->getPrevious();
        }
        $backtrace = $backtrace_exception->getTrace();
        // First trace is the error itself, already contained in the message.
        // While the second trace is the error source and also contained in the
        // message, the message doesn't contain argument values, so we output it
        // once more in the backtrace.
        array_shift($backtrace);

        // Generate a backtrace containing only scalar argument values.
        $error['@backtrace'] = Error::formatBacktrace($backtrace);
        $message = SafeMarkup::format('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);
      }
    }

    $content = $this->t('The website encountered an unexpected error. Please try again later.');
    $content .= $message ? '</br></br>' . $message : '';

    // Twig output.
    $twig_service = \Drupal::service('twig');
    $template_file = drupal_get_path('module', 'dyniva_core') . '/templates/error_500.html.twig';
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $variables = [
      'head_title' => 'Error',
      'html_attributes' => new Attribute(),
      'content' => $content,
    ];
    $variables['html_attributes']['lang'] = $language_interface->getId();
    $variables['html_attributes']['dir'] = $language_interface->getDirection();
    $content = $twig_service->loadTemplate($template_file)->render($variables);

    $response = new Response($content, 500, ['Content-Type' => 'text/html']);

    if ($exception instanceof HttpExceptionInterface) {
      $response->setStatusCode($exception->getStatusCode());
      $response->headers->add($exception->getHeaders());
    }
    else {
      $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR, '500 Service unavailable (with message)');
    }

    $event->setResponse($response);
  }

  /**
   * Cas注册用户名冲突处理.
   */
  public function onExternalauthAuthmapAlter($event) {
    $username_exists_rename = \Drupal::service('config.factory')->get('dyniva_core.cas.settings')->get('username_exists_rename');
    if(!$username_exists_rename) return;

    if($event instanceof \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent) {
      $entity_storage = \Drupal::service('entity.manager')->getStorage('user');
      $account_search = $entity_storage->loadByProperties(['name' => $event->getUsername()]);
      if (reset($account_search)) {
        $counter = 1;
        do {
          $username = $event->getUsername().'_'.$counter++;
          $account_search = $entity_storage->loadByProperties(['name' => $username]);
        } while (reset($account_search));
        $event->setUsername($username);
        \Drupal::logger('dyniva_core')->notice("Externalauth register rename to @name", ['@name' => $username]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run as the final (very late) KernelEvents::EXCEPTION subscriber.
    $events[KernelEvents::EXCEPTION][] = ['onException', -255];
    if(class_exists('\Drupal\externalauth\Event\ExternalAuthEvents')) {
      $events[\Drupal\externalauth\Event\ExternalAuthEvents::AUTHMAP_ALTER][] = ['onExternalauthAuthmapAlter', -255];
    }
    return $events;
  }

}
