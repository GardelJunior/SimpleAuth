<?php

/**
 * --------------------------------------------------------------------
 * CODEIGNITER 4 - SimpleAuth
 * --------------------------------------------------------------------
 *
 * This content is released under the MIT License (MIT)
 *
 * @package    SimpleAuth
 * @author     GeekLabs - Lee Skelding 
 * @license    https://opensource.org/licenses/MIT	MIT License
 * @link       https://github.com/GeekLabsUK/SimpleAuth
 * @since      Version 1.0
 * 
 */

namespace App\Controllers;

use App\Models\AuthModel;
use App\libraries\AuthLibrary;

class Auth extends BaseController
{
	public function __construct()
	{
		$this->AuthModel =	new AuthModel();
		$this->Session = session();		
		$this->Auth = new AuthLibrary;
		$this->config = config('Auth');
	}

	public function index()
	{
		// DIRECT TO LOGIN FORM
		return redirect()->to('login');
	}

	/*
	|--------------------------------------------------------------------------
	| USER LOGIN
	|--------------------------------------------------------------------------
	|
	| Get post data from login.php view
	| Set and Validate rules
	| Pass over to Library LoginUser
	| If successfull get user details from DB
	| Set user session
	| return true / false
	|
	*/
	public function login()
	{
		

        // CHECK IF COOKIE IS SET
		$this->Auth->checkCookie();

		// IF ITS A POST REQUEST DO YOUR STUFF ELSE SHOW VIEW
		if ($this->request->getMethod() == 'post') {

			//SET RULES
			$rules = [
				'email' => 'required|valid_email',
				'password' => 'required|min_length[6]|max_length[255]|validateUser[email,password]',
			];

			//VALIDATE RULES
			$errors = [
				'password' => [
					'validateUser' => 'Email or Password do not match',
				]
			];

			if (!$this->validate($rules, $errors)) {
				$data['validation'] = $this->validator;
			} else {

				// GET EMAIL FROM POST
				$email = $this->request->getVar('email');
				$rememberMe = $this->request->getVar('rememberme');

				$data = [
					'rememberme' => $rememberMe,
				];

				$this->Session->set($data);

				// LOG USER IN USING EMAIL
				$result = $this->Auth->Loginuser($email, $rememberMe);

				// CHECK RESULT REDIRECT TO DASHBOARD IF TRUE OR BACK TO LOGIN IF FALSE
				return redirect()->to($result);
				
			}
		}

		// SET UP VIEWS
		echo view('templates/header');
		echo view('login',['config' => $this->config]);
		echo view('templates/footer');
	}

	/*
	|--------------------------------------------------------------------------
	| REGISTER USER
	|--------------------------------------------------------------------------
	|
	| Get post data from register.php view
	| Set and Validate rules
	| pass over to library RegisterUser
	| If successfull save user details to DB
	| check if we should send activation email
	| return true / false
	|
	*/
	public function register()
	{

		// IF ITS A POST REQUEST DO YOUR STUFF ELSE SHOW VIEW
		if ($this->request->getMethod() == 'post') {

			// SET RULES
			$rules = [
				'firstname' => 'required|min_length[3]|max_length[25]',
				'lastname' => 'required|min_length[3]|max_length[25]',
				'email' => 'required|valid_email|is_unique[users.email]',
				'password' => 'required|min_length[6]|max_length[255]',
				'password_confirm' => 'matches[password]',
			];

			//VALIDATE RULES
			if (!$this->validate($rules)) {
				$data['validation'] = $this->validator;
			} else {

				// SET USER DATA
				$userData = [
					'firstname' => $this->request->getVar('firstname'),
					'lastname' => $this->request->getVar('lastname'),
					'email' => $this->request->getVar('email'),
					'password' => $this->request->getVar('password'),					
				];				

				// REGISTER USER
				$result = $this->Auth->RegisterUser($userData);	
				
				// CHECK RESULT
				if($result){

					return redirect()->to('/login');

				}
				
			}
		}

		echo view('templates/header');
		echo view('register');
		echo view('templates/footer');
	}

	/*
	|--------------------------------------------------------------------------
	| RESEND ACTIVATION EMAIL
	|--------------------------------------------------------------------------
	|
	| If user needs to resend activation email  
	|
	*/
	public function resendactivation($id)
	{

		$this->Auth->ResendActivation($id);		

		return redirect()->to('/login');		

	}


	/*
	|--------------------------------------------------------------------------
	| ACTIVATE USER
	|--------------------------------------------------------------------------
	|
	| Activate user account from email link 
	|
	*/
	public function activateUser($id, $token)
	{
	
		// ACTIVATE USER
		$this->Auth->activateuser($id, $token);		

		return redirect()->to('/');
	}

	/*
	|--------------------------------------------------------------------------
	| REGISTER USER
	|--------------------------------------------------------------------------
	|
	| Get post data from profile.php view
	| Set and Validate rules
	| Save to DB
	| Set session data
	|
	*/
	public function profile()
	{

		// IF ITS A POST REQUEST DO YOUR STUFF ELSE SHOW VIEW
		if ($this->request->getMethod() == 'post') {

			// SET UP RULES
			$rules = [
				'firstname' => 'required|min_length[3]|max_length[25]',
				'lastname' => 'required|min_length[3]|max_length[25]',
				'email' => 'required|valid_email',
			];

			// SET MORE RULES IF PASSWORD IS BEING CHANGED
			if ($this->request->getPost('password') != '') {
				$rules['password'] = 'required|min_length[6]|max_length[255]';
				$rules['password_confirm'] = 'matches[password]';
			}

			// VALIDATE RULES
			if (!$this->validate($rules)) {
				$data['validation'] = $this->validator;
			} else {

				// SET USER DATA
				$user = [
					'id' => $this->Session->get('id'),
					'firstname' => $this->request->getVar('firstname'),
					'lastname' => $this->request->getVar('lastname'),
					'email' => $this->request->getVar('email'),
					'role'	=> $this->Session->get('role'),
				];

				// IF PASSWORD IS LEFT EMPTY DO NOT CHANGE IT
				if ($this->request->getPost('password') != '') {
					$user['password'] = $this->request->getVar('password');
				}

				// SAVE TO DB
				$this->AuthModel->save($user);

				// SAVE USER DATA IN SESSION
				$this->Auth->setUserSession($user);

				// SET FLASH DATA
				$this->Session->setFlashData('success', lang('Auth.successUpdate'));

				return redirect()->to('/profile');
			}
		}

		$data['user'] = $this->AuthModel->where('id', $this->Session->get('id'))->first();

		echo view('templates/header', $data);
		echo view('profile');
		echo view('templates/footer');
	}



	/*
	|--------------------------------------------------------------------------
	| REGISTER USER
	|--------------------------------------------------------------------------
	|
	| Get post data from forgotpassword.php view
	| Set and Validate rules
	| Save to DB
	| Set session data
	|
	*/
	public function forgotPassword()
	{
		if ($this->request->getMethod() == 'post') {

			// SET UP RULES
			$rules = [
				'email' => 'required|valid_email|validateExists[email]',
			];

			// SET UP ERRORS
			$errors = [
				'email' => [
					'validateExists' => lang('Auth.noUser'),
				]
			];

			// CHECK VALIDATION
			if (!$this->validate($rules, $errors)) {

				$data['validation'] = $this->validator;
			}

			// VALIDATED
			else {

				// GET EMAIL FROM POST
				$email = $this->request->getVar('email');

				$this->Auth->ForgotPassword($email);
				
			}
		}

		echo view('templates/header');
		echo view('forgotpassword');
		echo view('templates/footer');
	}

	/*
	|--------------------------------------------------------------------------
	| RESET PASSWORD
	|--------------------------------------------------------------------------
	|
	| Takes the response from a a rest link from users reset email
	| Pass the user id and token to Library resetPassword();
	|
	*/
	public function resetPassword($id, $token)
	{
		// RESET PASSWORD 
		$id = $this->Auth->resetPassword($id, $token);
		
		// REDIRECT PASSING USER ID TO UPDATE PASSWORD FORM
		$this->updatepassword($id);
				
		
	}

	/*
	|--------------------------------------------------------------------------
	| UPDATE PASSWORD
	|--------------------------------------------------------------------------
	|
	| Get post data from resetpassword.php view
	| Save new password to DB 
	|
	*/
	public function updatepassword($id)
	{
		// IF ITS A POST REQUEST DO YOUR STUFF ELSE SHOW VIEW
		if ($this->request->getMethod() == 'post') {

			//SET RULES
			$rules = [
				'password' => 'required|min_length[6]|max_length[255]',
				'password_confirm' => 'matches[password]',
			];

			// VALIDATE RULES
			if (!$this->validate($rules)) {
				$data['validation'] = $this->validator;
			} else {
				
				// RULES PASSED
				$user = [
					'id' => $id,
					'password' => $this->request->getVar('password'),
					'reset_expire' => NULL, // RESET EXPIRY 
					'reset_token' => NULL, // CLEAR OLD TOKEN 
				];

				// UPDATE DB
				$this->AuthModel->save($user);

				// SET SOME FLASH DATA
				$this->Session->setFlashData('success', lang('Auth.resetSuccess'));

				return redirect()->to('/login');
			}
		}

		// SET USER ID TO PASS TO VIEW
		$data = [
			'id' => $id,
		];
		
		echo view('templates/header');
		echo view('resetpassword', $data);
		echo view('templates/footer');
	}

	public function lockscreen()
	{
		$result = $this->Auth->lockScreen();

        if ($result) {
            if ($this->request->getMethod() == 'post') {

            //SET RULES
                $rules = [
                'email' => 'required|valid_email',
                'password' => 'required|min_length[6]|max_length[255]|validateUser[email,password]',
            ];

                //VALIDATE RULES
                $errors = [
                'password' => [
                    'validateUser' => 'Wrong Password',
                ]
            ];

                if (!$this->validate($rules, $errors)) {
                    $data['validation'] = $this->validator;
                } else {

                // GET EMAIL FROM POST
                    $email = $this->request->getVar('email');

                    // LOG USER IN USING EMAIL
                    $result = $this->Auth->Loginuser($email);

                    // CHECK RESULT REDIRECT TO DASHBOARD IF TRUE OR BACK TO LOGIN IF FALSE
                    if ($result) {
                        return redirect()->to('dashboard');
                    }

                    return redirect()->to('dashboard');
                }
            }

            echo view('templates/header');
            echo view('lockscreen');
            echo view('templates/footer');
		}
		else {
            return redirect()->to('/');
        }
	}



	/*
	|--------------------------------------------------------------------------
	| LOG USER OUT
	|--------------------------------------------------------------------------
	|
	| Destroy session
	|
	*/
	public function logout()
	{
		$this->Auth->logout();

		return redirect()->to('/');
	}

	public function accessdenied()
	{
		
		echo view('error');
		
	}
}
