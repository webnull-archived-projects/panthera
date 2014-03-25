<?php
/**
 * Index step in Panthera installer
 * 
 * @package Panthera\installer
 * @author Damian Kęska
 * @author Mateusz Warzyński
 * @license GNU Lesser General Public License 3, see license.txt
 */

 /**
 * Index step in Panthera installer
 * 
 * @package Panthera\installer
 * @author Damian Kęska
 * @author Mateusz Warzyński
 */
 
class indexInstallerControllerSystem extends installerController
{
    /**
     * @var $requirements List of required modules
     */
    
    protected $requirements = array(
        'liblangtool'
    );
    
    /**
     * @var $config array List of configuration variables
     */
    
    protected $config = array(
        'language.enableselect' => array(
            'value' => TRUE, 'type' => 'bool', 'description' => array('Can user select a language?', 'installer'),
        ),
        
        'language.default' => array(
            'value' => 'english', 'type' => 'string', 'values' => array(), 'description' => array('Default selected language', 'installer'),
        ),
        
        'timezone.default' => array(
            'value' => '', 'type' => 'string', 'values' => array(), 'description' => array('Timezone set by default', 'installer'),
        ),
        
        'timezone.enableselect' => array(
            'value' => TRUE, 'type' => 'bool', 'description' => array('Can user select a timezone?', 'installer'),
        ),
    );
    
    /**
     * Prepare locales list, set locale
     * 
     * @author Damian Keska
     * @return null
     */
    
    protected function prepareLocales()
    {
        $locales = array();
        $locales['english'] = True; // english is by default
        
        foreach (localesManagement::getLocales() as $locale => $path)
        {
            if (localesManagement::getDomainDir($locale, 'installer'))
                $locales[$locale] = is_file(SITE_DIR. '/images/admin/flags/' .$locale. '.png');
        }
        
        $this -> config['language.default']['values'] = $locales;
        
        /** List of locales, current language **/
        
        $currentLocale = $this->config['language.default']['value'];
        
        if (localesManagement::getDomainDir($this -> panthera -> locale -> getActive(), 'installer'))
            $currentLocale = $this -> panthera -> locale -> getActive();
            
        if (is_file(SITE_DIR. '/images/admin/flags/' .$currentLocale. '.png'))
            $this -> panthera -> template -> push ('currentLocaleFlag', True);

        if ($this->config['language.enableselect']['value'])
        {
            $this -> panthera -> template -> push ('currentLocale', $currentLocale);
            $this -> panthera -> template -> push ('languages', $locales);
        }
    }

    /**
     * Prepare timezones to display
     * 
     * @author Damian Keska
     * @return null
     */

    protected function prepareTimezones()
    {
        /** Timezones **/
        
        $timezones = array();
        foreach (DateTimeZone::listIdentifiers() as $timezone)
        {
            $time = new DateTime('NOW');
            $time -> setTimezone(new DateTimeZone($timezone));
            
            $timezones[$timezone] = $time -> format($this -> panthera -> dateFormat);
        }
        
        $this->config['timezone.default']['values'] = $timezones;
        $defaultTimezone = $this->panthera->config->getKey('timezone');
        
        if ($this->config['timezone.default']['value'])
            $defaultTimezone = $this->config['timezone.default']['value'];
        
        $this -> panthera -> template -> push ('timezone', $defaultTimezone);
        
        if (isset($_GET['_timezone']))
        {
            if (in_array($_GET['_timezone'], DateTimeZone::listIdentifiers()))
            {
                $this -> panthera -> importModule('appconfig');
        
                try {
                    $appConfig = new appConfigEditor();
                    $appConfig -> config ['timezone'] = $_GET['_timezone'];
                    $appConfig -> save();
                    $this -> panthera -> config -> updateConfigCache($appConfig->config);
                    $this -> panthera -> template -> push ('timezone', $this -> panthera -> config->getKey('timezone'));
                } catch (Exception $e) {
                    $this -> panthera -> template -> push ('popupError', localize('Cannot save app.php', 'installer'). ', ' .localize('exception', 'installer'). ': ' .$e->getMessage());
                }
            }
        }
        
        if ($this->panthera->config->getKey('timezone') or !$this->config['timezone.enableselect']['value'])
            $this -> installer -> enableNextStep();
        
        if ($this->config['timezone.enableselect']['value'])
        {
            $time = new DateTime('NOW');
            $time -> setTimezone(new DateTimeZone($this->panthera->config->getKey('timezone')));
            $this -> panthera -> template -> push ('currentTime', $time->format('G:i:s d.m.Y'));
            $this -> panthera -> template -> push ('timezones', $timezones);
        }
    }

    /**
     * Constructor
     * 
     * @return null
     */

    public function prepare()
    {
        $this -> prepareLocales();
        $this -> prepareTimezones();
    }

    /**
     * Main function to display everything
     * 
     * @author Damian Keska
     * @return null
     */
    
	public function display()
	{
        $this -> installer -> setButton('back', False);
        $this -> installer -> template = 'index';
    }
}