<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author      Prestarocket <prestarocket@gmail.com>
 * @copyright   SARL JUST WEB
 * @license     Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class prestarockettheme extends Module
{
    protected $html = '';

    protected $errors = [];

    public function __construct()
    {
        $this->name = 'prestarockettheme';
        $this->author = 'Prestarocket';
        $this->version = '1.0.0';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Prestarocket Classic Rocket Theme', array(), 'Modules.Prestarocketclassic.Admin');
        $this->description = $this->trans('Prestarocket Classic Rocket Theme', array(), 'Modules.Prestarocketclassic.Admin');

        $this->ps_versions_compliancy = array(
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_
        );

        $this->imgUploadFolder = _PS_IMG_DIR_ . $this->name . DIRECTORY_SEPARATOR;
    }

    public function install()
    {
        return parent::install()
            && $this->installDir()
            && $this->registerHook('actionFrontControllerSetVariables');
    }

    protected function installDir()
    {
        if (!file_exists($this->imgUploadFolder)) {
            mkdir($this->imgUploadFolder);
        }

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDir();
    }

    protected function uninstallDir()
    {
        if (file_exists($this->imgUploadFolder)) {
            foreach (scandir($this->imgUploadFolder) as $file) {
                if (!in_array($file, ['.', '..'])) {
                    unlink($file);
                }
            }

            unlink($this->imgUploadFolder);
        }

        return true;
    }

    public function hookActionFrontControllerSetVariables()
    {
        $source_file = '';
        if (file_exists($this->imgUploadFolder . 'logo.svg')) {
            $source_file = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . 'logo.svg'));
            $source_file .= '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');
        }

        return array(
            'logo_svg' => $source_file
        );
    }

    public function getContent()
    {
        $this->postProcess();
        if (count($this->errors)) {
            $this->html = $this->displayError($this->errors);
        }

        return $this->html . $this->renderForm();
    }

    protected function postProcess()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            if (!isset($_FILES['ROCKETCLASSIC_SVG'])) {
                $this->errors[] = $this->l('Wrong! There is no file uploaded.');
                return false;
            } else if ($_FILES['ROCKETCLASSIC_SVG']['type'] !== 'image/svg+xml') {
                $this->errors[] = $this->l('Wrong! Uploaded file is not a valid svg file.');
                return false;
            } else if ($_FILES['ROCKETCLASSIC_SVG']['error']) {
                $this->errors[] = $this->getUploadErrorMessage($_FILES['ROCKETCLASSIC_SVG']['error']);
                return false;
            } else if (!move_uploaded_file($_FILES['ROCKETCLASSIC_SVG']['tmp_name'], $this->imgUploadFolder . 'logo.svg')) {
                $this->errors[] = $this->l('Wrong! The file has not been uploaded.');
                return false;
            } else if(!ctype_digit(Configuration::get('ROCKETCLASSIC_SVG_WIDTH')) ||!ctype_digit(Configuration::get('ROCKETCLASSIC_SVG_HEIGHT'))) {
                $this->errors[] = $this->l('Please enter a valid number');
                return false;
            }

            Configuration::updateValue('PRESTAROCKETCLASSIC_UPLOAD_DATE', date('YmdHis'));
            Configuration::updateValue('ROCKETCLASSIC_SVG_WIDTH', Tools::getValue('ROCKETCLASSIC_SVG_WIDTH'));
            Configuration::updateValue('ROCKETCLASSIC_SVG_HEIGHT', Tools::getValue('ROCKETCLASSIC_SVG_HEIGHT'));
            Configuration::updateValue('ROCKETCLASSIC_SVG_LOGO', Tools::getValue('ROCKETCLASSIC_SVG_LOGO'));
            $this->html .= $this->displayConfirmation($this->l('File uploaded!'));
        }
    }

    protected function renderForm()
    {
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Banner configuration')
            ],
            'input' => [
                1 => [
                    'type' => 'file',
                    'label' => $this->l('Logo SVG'),
                    'name' => 'ROCKETCLASSIC_SVG',
                    'required' => true
                ],
                'width' => [
                    'type' => 'text',
                    'label' => $this->l('Image width'),
                    'name' => 'ROCKETCLASSIC_SVG_WIDTH',
                    'required' => true
                ],
                'height' => [
                    'type' => 'text',
                    'label' => $this->l('Image height'),
                    'name' => 'ROCKETCLASSIC_SVG_HEIGHT',
                    'required' => true
                ],
                'logo' => [
                    'type' => 'text',
                    'label' => $this->l('Logo\'s pathing'),
                    'name' => 'ROCKETCLASSIC_SVG_LOGO',
                    'required' => true
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        if (file_exists($this->imgUploadFolder . 'logo.svg')) {
            $source_file = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . 'logo.svg'));
            $source_file .= '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');

            $fieldsForm[0]['form']['input'][0] = [
                'type' => 'html',
                'name' => 'ROCKETCLASSIC_SVG_PREVIEW',
                'html_content' => '<img src="' . $source_file . '" alt="ROCKETCLASSIC_SVG_PREVIEW">'
            ];
        }

        sort($fieldsForm[0]['form']['input']);

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm($fieldsForm);
    }

    protected function getConfigFieldsValues()
    {
        return [
            'ROCKETCLASSIC_SVG_WIDTH' => Tools::getValue('ROCKETCLASSIC_SVG_WIDTH', Configuration::get('ROCKETCLASSIC_SVG_WIDTH')),
            'ROCKETCLASSIC_SVG_HEIGHT' => Tools::getValue('ROCKETCLASSIC_SVG_HEIGHT', Configuration::get('ROCKETCLASSIC_SVG_HEIGHT')),
            'ROCKETCLASSIC_SVG_LOGO' => Tools::getValue('ROCKETCLASSIC_SVG_LOGO', Configuration::get('ROCKETCLASSIC_SVG_LOGO'))
        ];
    }

    private function getUploadErrorMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = $this->l('Wrong! The uploaded file exceeds the upload_max_filesize directive in php.ini');
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = $this->l('Wrong! The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = $this->l('Wrong! The uploaded file was only partially uploaded');
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = $this->l('Wrong! No file was uploaded');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = $this->l('Wrong! Missing a temporary folder');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = $this->l('Wrong! Failed to write file to disk');
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = $this->l('Wrong! File upload stopped by extension');
                break;
            default:
                $message = $this->l('Wrong! Unknown upload error');
                break;
        }

        return $message;
    }
}
