<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\CartRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\UpdateUser1C;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private CartRepository $cartRepository;
    private UpdateUser1C $updateUser1C;

    public function __construct(UrlGeneratorInterface $urlGenerator, CartRepository $cartRepository, UpdateUser1C $updateUser1C)
    {
        $this->urlGenerator = $urlGenerator;
        $this->cartRepository = $cartRepository;
        $this->updateUser1C = $updateUser1C;
    }

    public function authenticate(Request $request): PassportInterface
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->get('ces_login')),
                new RememberMeBadge()
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
        //     return new RedirectResponse($targetPath);
        // }

        // For example:
        //return new RedirectResponse($this->urlGenerator->generate('some_route'));
        // throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);

        // return new RedirectResponse($this->urlGenerator->generate('homepage'));

        $user = $token->getUser();

        // Обновляем данные юзера из 1С
        $this->updateUser1C->updateUser1C($user->getId());
        // Если в корзине есть товары у них надо добавить владельца
        $this->cartRepository->cartOnwer($request->getSession()->get('cart_user_id'), $user->getId());
        
        return new JsonResponse([
            'ok' => 'ok',
            'data' => $this->urlGenerator->generate('profile')
        ], 200); 
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
