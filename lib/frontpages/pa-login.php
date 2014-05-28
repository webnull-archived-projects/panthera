<?php
/**
 * Admin panel login front controller
 *
 * @package Panthera\core\user\login
 * @config pa-login redirect_after_login
 * @config pa-login login.failures.max
 * @config pa-login login.failures.bantime
 * @config ajax_url
 * @author Damian Kęska
 * @license LGPLv3
 */
  
define('SKIP_MAINTENANCE_CHECK', TRUE);

require_once 'content/app.php';
include getContentDir('pageController.class.php');

/**
 * Admin panel login front controller
 *
 * @package Panthera\core\user\login
 * @config pa-login redirect_after_login
 * @config pa-login login.failures.max
 * @config pa-login login.failures.bantime
 * @config ajax_url
 * @author Damian Kęska
 */

class pa_loginControllerSystem extends pageController
{
    protected $requirements = array(
        'userregistration', 'passwordrecovery',
    );
    
    /**
     * Support for login extensions
     * 
     * @return null
     * @author Damian Kęska
     */
    
    public function loadExtensions()
    {
        $extensions = $this -> panthera -> config -> getKey('login.extensions', array(
            'facebook',
            'lastloginhistory',
            'passwordrecovery',
        ), 'array', 'pa-login');
        
        foreach ($extensions as $extension)
        {
            if ($this -> panthera -> moduleExists('login/' .$extension))
            {
                $object = $this -> panthera -> importModule('login/' .$extension, true);
                
                if (is_object($object) and method_exists($object, 'initialize'))
                    $object -> initialize($this);
            }
        }
    }
    
    /**
     * Logout action
     * 
     * @return null
     */
    
    public function logoutAction()
    {
        if (isset($_GET['logout']))
            userTools::logoutUser();
    }

    /**
     * Main function
     * 
     * @return null
     */
    
    public function display()
    {
        // logout user, TODO: CHANGE TO POST
        $this -> logoutAction();
        $this -> loadExtensions();
        
        // redirect user if it's already logged in
        if(checkUserPermissions($user))
        {
            if (!$this -> checkPermissions('admin.accesspanel', true))
            {
                pa_redirect($this -> panthera -> config -> getKey('redirect_after_login', 'index.php', 'string', 'pa-login'));
                pa_exit(); // just in case
            }
            
            pa_redirect('pa-admin.php');
        }
        
        /**
         * Get list of all locales to display flags on page
         * 
         * @author Damian Kęska
         */
        
        $locales = $this -> panthera -> locale -> getLocales();
        $localesTpl = array();
        
        foreach ($locales as $lang => $enabled)
        {
            if ($enabled == True)
            {
                if (is_file(SITE_DIR. '/images/admin/flags/' .$lang. '.png'))
                    $localesTpl[] = $lang;
            }
        }
        
        $this -> panthera -> template -> push('flags', $localesTpl);
        
        // check authorization
        if (isset($_POST['log']) or isset($_GET['key']) or isset($_GET['ckey']))
            $this -> checkAuthData();
        
        // save the referer when logging in
        if (strpos($_SERVER['HTTP_REFERER'], $this -> panthera -> config -> getKey('ajax_url')) !== False and strpos($_SERVER['HTTP_REFERER'], '&cat=admin') !== False)
            $this -> panthera -> session -> set('login_referer', $_SERVER['HTTP_REFERER']);
        
        $this -> panthera -> template -> setTitle(localize('Log in'));
        $this -> panthera -> template -> setTemplate('admin');
        $this -> panthera -> template -> display('login.tpl');
        pa_exit();
    }

    /**
     * Check authorization data
     * 
     * @return null
     */

    public function checkAuthData()
    {
        $continueChecking = True;
        
        $u = new pantheraUser('login', $_POST['log']);
        $this -> getFeatureRef('login.checkauth', $continueChecking, $u);
        $this -> panthera -> template -> setTemplate('admin');
        
        // if module decided to break 
        if (!$continueChecking or is_string($continueChecking))
        {
            if (is_string($continueChecking))
                $this -> panthera -> template -> push('message', $continueChecking);
            
            $this -> panthera -> template -> display('login.tpl');
            pa_exit();
        }
            
        if ($u -> exists())
        {
            if ($u -> attributes -> get('loginFailures') >= intval($this -> panthera -> config -> getKey('login.failures.max', 5, 'int', 'pa-login')) and $u -> attributes -> get('loginFailures') !== 0)
            {
                if (intval($u -> attributes -> get('loginFailureExpiration')) <= time())
                {
                    $u -> attributes -> set('loginFailures', 0);
                    $u -> attributes -> remove('loginFailureExpiration');
                    $u -> save();
                        
                } else {
                    $this -> panthera -> get_options('login.failures.exceeded', array('user' => $u, 'failures' => $u -> attributes -> get('loginFailures'), 'expiration' => $u -> attributes -> get('loginFailureExpiration')));
                    $this -> panthera -> template -> push('message', localize('Number of login failures exceeded, please wait a moment before next try', 'messages'));
                    $this -> panthera -> template -> display('login.tpl');
                    pa_exit();
                }
            }
        }
            
        $result = userTools::userCreateSession($_POST['log'], $_POST['pwd']);
            
        /**
         * Successful login
         *
         * @author Damian Kęska
         */
            
        if($result and is_bool($result))
        {
            $this -> getFeature('login.success', $u);
            
            $u -> attributes -> set('loginFailures', 0);
            $u -> attributes -> remove('loginFailureExpiration');
            $u -> save();

            // if user cannot access Admin Panel, redirect to other location (specified in redirect_after_login config section)
            if (!$this -> checkPermissions('admin.accesspanel', true))
                pa_redirect($this -> panthera -> config -> getKey('redirect_after_login', 'index.php', 'string', 'pa-login'));
            
            if ($this -> panthera -> session -> exists('login_referer'))
            {
                header('Location: ' .$this -> panthera -> session -> get('login_referer'));
                $this -> panthera -> session -> remove ('login_referer');
                pa_exit();
            }
            
            pa_redirect('pa-admin.php');
            pa_exit();
    
        /**
         * Suspended/banned account
         *
         * @author Damian Kęska
         */
    
        } elseif ($result === 'BANNED') {
            $this -> panthera -> template -> push('message', localize('This account has been suspended, please contact administrator for details', 'messages'));
            
            $this -> getFeature('login.failures.suspended', array(
                'user' => $u,
                'failures' => $u -> attributes -> get('loginFailures'),
                'expiration' => $u -> attributes -> get('loginFailureExpiration'),
            ));
    
    
        /**
         * Login failure
         *
         * @author Damian Kęska
         */
    
        } elseif ($result === False) {
            $this -> panthera -> template -> push('message', localize('Invalid user name or password', 'messages'));
                
            if ($u -> exists())
            {
                $u -> attributes -> set('loginFailures', intval($u -> attributes -> get('loginFailures'))+1);
                $banned = False;
                    
                if ($u -> attributes -> get('loginFailures') >= intval($this -> panthera -> config -> getKey('login.failures.max', 5, 'int', 'pa-login')))
                {
                    $banned = True;
                    $u -> attributes -> set('loginFailureExpiration', (time()+intval($this -> panthera -> config -> getKey('login.failures.bantime', 300, 'int', 'pa-login')))); // 5 minutes by default
                }
                   
                $this -> getFeature('login.failure', array(
                    'user' => $u,
                    'failures' => $u -> attributes -> get('loginFailures'),
                    'expiration' => $u -> attributes -> get('loginFailureExpiration'),
                    'banned' => $banned,
                ));

                $u -> attributes -> set('lastFailLoginIP', $_SERVER['REMOTE_ADDR']);
                $u -> save();
            }
        }
    }
}

// if you want to copy this front controller to your site directory instead of linking please change PANTHERA_DIR to SITE_DIR inside of your copy or you can make a include
if (strpos(__FILE__, PANTHERA_DIR) !== FALSE)
{
    $object = new pa_loginControllerSystem();
    $object -> display();   
}