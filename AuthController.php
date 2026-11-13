<?php
declare(strict_types=1);


namespace App\Controllers;


use App\Models\User;
use App\Session;
use App\View;


final class AuthController
{
    private User $users;


    public function __construct()
    {
        $this->users = new User();
    }


    // ---------- Registration ----------


    public function showRegister(): void
    {
        View::render('register', ['title' => 'Register']);
    }


    public function register(): void
    {
        if (!Session::checkCsrf($_POST['csrf_token'] ?? null)) {
            View::error(403, 'Invalid or missing CSRF token.');
            return;
        }


        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');


        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3–30 letters, numbers or underscores.';
        }
        if (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be 8–72 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (!$errors && $this->users->usernameExists($username)) {
            $errors[] = 'That username is already taken.';
        }
        if (!$errors && $this->users->emailExists($email)) {
            $errors[] = 'An account with that email already exists.';
        }


        if ($errors) {
            View::render('register', [
                'title'    => 'Register',
                'errors'   => $errors,
                'old'      => ['username' => $username, 'email' => $email], // never echo the password
            ], 422);
            return;
        }


        try {
            $this->users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') { // unique constraint (race condition)
                View::render('register', ['title' => 'Register', 'errors' => ['That account already exists.'],
                    'old' => ['username' => $username, 'email' => $email]], 422);
                return;
            }
            throw $e;
        }


        Session::flash('Account created. You can now log in.');
        View::redirect('/login');
    }


    // ---------- Login / logout ----------


    public function showLogin(): void
    {
        View::render('login', ['title' => 'Log in']);
    }


    public function login(): void
    {
        if (!Session::checkCsrf($_POST['csrf_token'] ?? null)) {
            View::error(403, 'Invalid or missing CSRF token.');
            return;
        }


        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user     = $this->users->findByUsername($username);


        // A NULL password_hash (seed users) must never authenticate.
        $ok = $user !== null
            && $user['password_hash'] !== null
            && password_verify($password, $user['password_hash']);


        if (!$ok) {
            View::render('login', [
                'title'  => 'Log in',
                'errors' => ['Incorrect username or password.'],
                'old'    => ['username' => $username],
            ], 401);
            return;
        }


        Session::login((int) $user['id'], $user['username']); // regenerates session ID
        Session::flash('Welcome, ' . $user['username'] . '!');
        View::redirect('/');
    }


    public function logout(): void
    {
        if (!Session::checkCsrf($_POST['csrf_token'] ?? null)) {
            View::error(403, 'Invalid or missing CSRF token.');
            return;
        }
        Session::logout();
        View::redirect('/');
    }
}