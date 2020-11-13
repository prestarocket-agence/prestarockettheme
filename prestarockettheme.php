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
        Configuration::deleteByName('ROCKETCLASSIC_SVG');
        Configuration::deleteByName('ROCKETCLASSIC_ACCOUNT_TITLE');
        Configuration::deleteByName('ROCKETCLASSIC_ACCOUNT_DESCRIPTION');
        Configuration::deleteByName('ROCKETCLASSIC_CATEGORY');
        Configuration::deleteByName('ROCKETCLASSIC_ACCOUNT');
        Configuration::deleteByName('ROCKETCLASSIC_SVG_WIDTH');
        Configuration::deleteByName('ROCKETCLASSIC_SVG_HEIGHT');

        return parent::uninstall();
    }

    public function hookActionFrontControllerSetVariables()
    {
        if (Configuration::get('ROCKETCLASSIC_SVG')) {
            $svg_link = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . Configuration::get('ROCKETCLASSIC_SVG')));
            $source_file = $svg_link . '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');
        }
        if (Configuration::get('ROCKETCLASSIC_ACCOUNT')) {
            $account_link = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . Configuration::get('ROCKETCLASSIC_ACCOUNT')));
            $account_file = $account_link . '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');
        } else {
            $account_file = '';
            $source_file = '';
        }

        return array(
            'svg' => array(
                'logo_svg' => $source_file,
                'size_svg' => array(
                    'width' => (string)Tools::getValue('ROCKETCLASSIC_SVG_WIDTH', Configuration::get('ROCKETCLASSIC_SVG_WIDTH')),
                    'height' => (string)Tools::getValue('ROCKETCLASSIC_SVG_HEIGHT', Configuration::get('ROCKETCLASSIC_SVG_HEIGHT')),
                ),
            ),
            'account' => array(
                'title_account' => (string)Tools::getValue('ROCKETCLASSIC_ACCOUNT_TITLE', Configuration::get('ROCKETCLASSIC_ACCOUNT_TITLE')),
                'description_account' => (string)Tools::getValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION', Configuration::get('ROCKETCLASSIC_ACCOUNT_DESCRIPTION')),
                'image_account' => $account_file,
            ),
            'category' => array(
                'category_switch' => Tools::getValue('ROCKETCLASSIC_CATEGORY', Configuration::get('ROCKETCLASSIC_CATEGORY')),
            ),
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
            if (!$this->svgHandler()) {
                return false;
            } else if (!$this->accoutnHandler()) {
                return false;
            }
            $this->updateAll();
            $this->updateImageSize();
            $this->html .= $this->displayConfirmation($this->l('File uploaded!'));
        }
        return true;
    }

    protected function accoutnHandler()
    {
        if ($_FILES['ROCKETCLASSIC_ACCOUNT']['error'] == 0) {
            if (ImageManager::validateUpload($_FILES['ROCKETCLASSIC_ACCOUNT'], 4000000)) {
                $this->errors[] = $this->l('Wrong! Uploaded file is not a valid file.');
                return false;
            }
        }
        return true;
    }

    protected function svgHandler()
    {
        if (!isset($_FILES['ROCKETCLASSIC_SVG'])) {
            $this->errors[] = $this->l('Wrong! There is no file uploaded.');
            return false;
        } else if ($_FILES['ROCKETCLASSIC_SVG']['type'] !== 'image/svg+xml') {
            $this->errors[] = $this->l('Wrong! Uploaded file is not a valid file.');
            return false;
        } else if ($_FILES['ROCKETCLASSIC_SVG']['error']) {
            $this->errors[] = $this->getUploadErrorMessage($_FILES['ROCKETCLASSIC_SVG']['error']);
            return false;
        } else if (!move_uploaded_file($_FILES['ROCKETCLASSIC_SVG']['tmp_name'], $this->imgUploadFolder . 'logo.svg')) {
            $this->errors[] = $this->l('Wrong! The file has not been uploaded.');
            return false;
        }
        return true;
    }

    protected function  updateAll()
    {
        Configuration::updateValue('PRESTAROCKETCLASSIC_UPLOAD_DATE', date('YmdHis'));
        Configuration::updateValue('ROCKETCLASSIC_SVG', Tools::getValue('ROCKETCLASSIC_SVG'));
        Configuration::updateValue('ROCKETCLASSIC_ACCOUNT', Tools::getValue('ROCKETCLASSIC_ACCOUNT'));
        Configuration::updateValue('ROCKETCLASSIC_ACCOUNT_TITLE', Tools::getValue('ROCKETCLASSIC_ACCOUNT_TITLE'));
        Configuration::updateValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION', Tools::getValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION'));
        Configuration::updateValue('ROCKETCLASSIC_CATEGORY', Tools::getValue('ROCKETCLASSIC_CATEGORY'));
    }

    public function updateImageSize()
    {
        $svg_link = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . Configuration::get('ROCKETCLASSIC_SVG')));

        $xml = file_get_contents($svg_link);
        $xmlget = simplexml_load_string($xml);
        $xmlattributes = $xmlget->attributes();
        $xmlwidth = (string)$xmlattributes->width;
        $xmlheigth = (string)$xmlattributes->height;
        Configuration::updateValue('ROCKETCLASSIC_SVG_HEIGHT', $xmlheigth);
        Configuration::updateValue('ROCKETCLASSIC_SVG_WIDTH', $xmlwidth);
    }

    protected function renderForm()
    {
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Banner configuration')
            ],
            'tabs' => array(
                'svg' => $this->l('Image SVG'),
                'account' => $this->l('my account'),
                'category' => $this->l('categories')
            ),
            'input' => [
                'svg_file' => [
                    'type' => 'file',
                    'label' => $this->l('Logo SVG'),
                    'name' => 'ROCKETCLASSIC_SVG',
                    'tab' => 'svg',
                    'required' => true
                ],
                'account_image' => [
                    'type' => 'file',
                    'label' => $this->l('Account Picture'),
                    'name' => 'ROCKETCLASSIC_ACCOUNT',
                    'tab' => 'account',
                    'required' => false
                ],
                'account_title' => array(
                    'type' => 'text',
                    'label' => $this->l('Account title'),
                    'name' => 'ROCKETCLASSIC_ACCOUNT_TITLE',
                    'tab' => 'account',
                    'required' => false
                ),
                'account_description' => array(
                    'type' => 'textarea',
                    'label' => $this->trans('Description', [], 'Modules.rocketCustomtext.Admin'),
                    'name' => 'ROCKETCLASSIC_ACCOUNT_DESCRIPTION',
                    'cols' => 40,
                    'rows' => 10,
                    'tab' => 'account',
                    'class' => 'rte',
                    'autoload_rte' => true,
                    'required' => false
                ),
                'category_switch' => array(
                    'type' => 'switch',
                    'label' => $this->l('Show the sub_categories'),
                    'name' => 'ROCKETCLASSIC_CATEGORY',
                    'desc' => $this->l('Please select if you want to show the sub-categories'),
                    'required' => false,
                    'tab' => 'category',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        ),
                    )
                ),
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'tab' => 'svg'
            ],
        ];

        if (isset($_FILES['ROCKETCLASSIC_SVG'])) {
            $svg_source_file = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . Configuration::get('ROCKETCLASSIC_SVG')));
            $svg_source_file .= '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');

            $fieldsForm[0]['form']['input'][0] = [
                'type' => 'html',
                'name' => 'ROCKETCLASSIC_SVG_PREVIEW',
                'tab' => 'svg',
                'html_content' => '<img src="' . $svg_source_file . '" alt="ROCKETCLASSIC_SVG_PREVIEW">'
            ];
        }

        if (isset($_FILES['ROCKETCLASSIC_ACCOUNT'])) {
            $svg_source_file = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . Configuration::get('ROCKETCLASSIC_ACCOUNT')));
            $svg_source_file .= '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');

            $fieldsForm[0]['form']['input'][1] = [
                'type' => 'html',
                'name' => 'ROCKETCLASSIC_ACCOUNT_PREVIEW',
                'tab' => 'account',
                'html_content' => '<img src="' . $svg_source_file . '" alt="ROCKETCLASSIC_ACCOUNT_PREVIEW">'
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
            'ROCKETCLASSIC_SVG' => Tools::getValue('ROCKETCLASSIC_SVG', Configuration::get('ROCKETCLASSIC_SVG')),
            'ROCKETCLASSIC_ACCOUNT' => Tools::getValue('ROCKETCLASSIC_ACCOUNT', Configuration::get('ROCKETCLASSIC_ACCOUNT')),
            'ROCKETCLASSIC_ACCOUNT_TITLE' => Tools::getValue('ROCKETCLASSIC_ACCOUNT_TITLE', Configuration::get('ROCKETCLASSIC_ACCOUNT_TITLE')),
            'ROCKETCLASSIC_ACCOUNT_DESCRIPTION' => Tools::getValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION', Configuration::get('ROCKETCLASSIC_ACCOUNT_DESCRIPTION')),
            'ROCKETCLASSIC_CATEGORY' => Tools::getValue('ROCKETCLASSIC_CATEGORY', Configuration::get('ROCKETCLASSIC_CATEGORY'))
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
