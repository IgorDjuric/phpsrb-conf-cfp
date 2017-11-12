<?php

namespace OpenCFP\Http\Controller;

use Cartalyst\Sentry\Users\UserExistsException;
use OpenCFP\Domain\Services\AccountManagement;
use OpenCFP\Domain\Services\Authentication;
use OpenCFP\Domain\ValidationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SignupController extends BaseController
{
    use FlashableTrait;

    public function indexAction()
    {
        $auth = $this->service(Authentication::class);

        if ($auth->check()) {
            return $this->redirectTo('dashboard');
        }

        $cfp = $this->service('callforproposal');

        if (! $cfp->isOpen()) {
            $this->service('session')->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'Sorry, the call for papers has ended.',
            ]);

            return $this->redirectTo('homepage');
        }

        return $this->render('security/signup.twig');
    }

    public function processAction(Request $req, \OpenCFP\Application $app)
    {
        try {
            $this->validate([
                'email' => 'required|email',
                'password' => 'required',
                'coc' => 'accepted',
            ]);

            /** @var AccountManagement $accounts */
            $accounts = $this->service(AccountManagement::class);

            $user = $accounts->create($req->get('email'), $req->get('password'), [
                'activated' => 1,
            ]);

            // This is for redirecting to OAuth endpoint if we arrived
            // as part of the Authorization Code Grant flow.
            if ($this->service('session')->has('redirectTo')) {
                $this->service(Authentication::class)->authenticate($req->get('email'), $req->get('password'));

                return new RedirectResponse($this->service('session')->get('redirectTo'));
            }

            $app['session']->set('flash', [
                'type' => 'success',
                'short' => 'Success',
                'ext' => "You've successfully created your account!",
            ]);

            // Automatically authenticate the newly created user.
            $this->service(Authentication::class)->authenticate($req->get('email'), $req->get('password'));

            return $this->redirectTo('dashboard');
        } catch (ValidationException $e) {
            $app['session']->set('flash', [
                'type' => 'error',
                'short' => $e->getMessage(),
                'ext' => $e->errors(),
            ]);

            return $this->redirectBack();
        } catch (UserExistsException $e) {
            $app['session']->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'A user already exists with that email address',
            ]);

            return $this->redirectBack();
        }
    }
}
