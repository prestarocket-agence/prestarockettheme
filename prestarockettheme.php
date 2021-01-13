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
        Configuration::updateValue('ROCKET_PRODUCT_TABS_TYPE',1);
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
        Configuration::deleteByName('ROCKET_LOGO_SVG');
        Configuration::deleteByName('ROCKETCLASSIC_ACCOUNT_TITLE');
        Configuration::deleteByName('ROCKETCLASSIC_ACCOUNT_DESCRIPTION');
        Configuration::deleteByName('ROCKETCLASSIC_CATEGORY');
        Configuration::deleteByName('ROCKETCLASSIC_ACCOUNT');
        Configuration::deleteByName('ROCKET_LOGO_SVG_WIDTH');
        Configuration::deleteByName('ROCKET_LOGO_SVG_HEIGHT');

        return parent::uninstall();
    }

    public function hookActionFrontControllerSetVariables()
    {
        $account_file = false;
        $svg_logo_url = Configuration::get('ROCKET_LOGO_SVG');

        if (Configuration::get('ROCKETCLASSIC_ACCOUNT')) {
            $account_link = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder . Configuration::get('ROCKETCLASSIC_ACCOUNT')));
            $account_file = $account_link . '?v=' . Configuration::get('PRESTAROCKETCLASSIC_UPLOAD_DATE');
        }

        $vars = array(
            'logo' => array(
                'url' => $svg_logo_url,
                'width' => Configuration::get('ROCKET_LOGO_SVG_WIDTH'),
                'height' => Configuration::get('ROCKET_LOGO_SVG_HEIGHT')
            ),
            'account' => array(
                'title_account' => Configuration::get('ROCKETCLASSIC_ACCOUNT_TITLE'),
                'description_account' => Configuration::get('ROCKETCLASSIC_ACCOUNT_DESCRIPTION'),
                'image_account' => $account_file,
            ),
            'category' => array(
                'category_switch' => Configuration::get('ROCKETCLASSIC_CATEGORY')
            ),
        );
        $product_page_option = $this->setFrontVarProduct();
        $vars = array_merge($vars,$product_page_option);
        return $vars;
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

            $this->postProcessProduct();

            //@todo refacto
            $this->svgHandler();
            $this->accoutnHandler();
            $this->updateAll();
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
        $errors_svg_upload = array();
        $logo_name = $this->getLogoName();
        if (isset($_FILES['ROCKET_LOGO_SVG'])) {
            if ($_FILES['ROCKET_LOGO_SVG']['type'] !== 'image/svg+xml') {
                $errors_svg_upload[] = $this->l('Wrong! Uploaded file is not a svg file.');
            } else if ($_FILES['ROCKET_LOGO_SVG']['error']) {
                $errors_svg_upload[] = $this->getUploadErrorMessage($_FILES['ROCKET_LOGO_SVG']['error']);
            } else if (!move_uploaded_file($_FILES['ROCKET_LOGO_SVG']['tmp_name'], $this->imgUploadFolder . $logo_name)) {
                $errors_svg_upload[] = $this->l('Wrong! The file has not been uploaded.');
            }
            if(empty($errors_svg_upload)){
                $logo_svg_url = _PS_IMG_ . $this->name . DIRECTORY_SEPARATOR . $logo_name;
                Configuration::updateValue('ROCKET_LOGO_SVG', $logo_svg_url);
                Configuration::updateValue('ROCKET_LOGO_SVG_NAME', $logo_name);

                $this->updateImageSize();

            }else{
                $this->errors = array_merge($this->errors,$errors_svg_upload);
            }

        }
    }

    protected function  updateAll()
    {

        Configuration::updateValue('ROCKETCLASSIC_ACCOUNT', Tools::getValue('ROCKETCLASSIC_ACCOUNT'));
        Configuration::updateValue('ROCKETCLASSIC_ACCOUNT_TITLE', Tools::getValue('ROCKETCLASSIC_ACCOUNT_TITLE'));
        Configuration::updateValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION', Tools::getValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION'));
        Configuration::updateValue('ROCKETCLASSIC_CATEGORY', Tools::getValue('ROCKETCLASSIC_CATEGORY'));
    }

    protected function postProcessProduct()
    {
        return Configuration::updateValue('ROCKET_PRODUCT_TABS_TYPE', Tools::getValue('ROCKET_PRODUCT_TABS_TYPE'));

    }

    protected function setFrontVarProduct()
    {
        return array(
            'product_tabs' => Configuration::get('ROCKET_PRODUCT_TABS_TYPE')
        );
    }

    public function updateImageSize()
    {
        $logo_name = Configuration::get('ROCKET_LOGO_SVG_NAME');
        $width = false;
        $height = false;

        $file = $this->imgUploadFolder . $logo_name;
        if ($file) {
            $xml = file_get_contents($file);
            $xmlget = simplexml_load_string($xml);
            $xmlattributes = $xmlget->attributes();
            $xmlwidth = (string)$xmlattributes->width;
            $xmlheight = (string)$xmlattributes->height;

            if (strpos($xmlwidth, 'px')) {
                $width = (int)str_replace('px','',$xmlwidth);
            }
            if (strpos($xmlheight, 'px')) {
                $height = (int)str_replace('px','',$xmlheight);
            }
        }

        Configuration::updateValue('ROCKET_LOGO_SVG_HEIGHT', $height);
        Configuration::updateValue('ROCKET_LOGO_SVG_WIDTH', $width);
    }

    protected function renderForm()
    {
        $logo_svg = Configuration::get('ROCKET_LOGO_SVG');

        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Classic rocket theme configuration')
            ],
            'tabs' => array(
                'svg' => $this->l('Logo SVG'),
                'account' => $this->l('my account'),
                'category' => $this->l('categories'),
                'product' => $this->l('Product page')
            ),
            'input' => [
                'svg_logo_preview' => [
                    'type' => 'html',
                    'tab' => 'svg',
                    'name' => 'ROCKET_LOGO_SVG_PREVIEW',
                    'html_content' =>  ($logo_svg ? '<div class="col-lg-9 col-lg-offset-3"><img src="' . $logo_svg . '" alt="ROCKET_LOGO_SVG_PREVIEW" width="200" height="auto"></div>' : '')
                ],
                'svg_file' => [
                    'type' => 'file',
                    'label' => $this->l('Logo SVG'),
                    'name' => 'ROCKET_LOGO_SVG',
                    'desc' => $this->l('Upload a svg logo for your shop'),
                    'tab' => 'svg',
                    'required' => false
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

        $fieldsForm[0]['form']['input'][] = $this->renderFormProduct();


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

    protected function renderFormProduct()
    {
        $product_tabs_options = [
            ['id_product_tab'=>'tabs','name'=>'Tabs'],
            ['id_product_tab'=>'collapse','name'=>'Collapse'],
            ['id_product_tab'=>'columns','name'=>'In columns']
            ];
        return [
            'type' => 'select',
            'tab' => 'product',
            'label' => $this->l('Product tabs layout'),
            'name' => 'ROCKET_PRODUCT_TABS_TYPE',
            'desc' => $this->l('How to display product tabs on product page'),
            'required' => false,
            'options' => [
                'query' => $product_tabs_options,
                'id' => 'id_product_tab',
                'name' => 'name'
            ],
        ];
    }

    protected function getConfigFieldsValues()
    {

        return [
            'ROCKET_LOGO_SVG' => Tools::getValue('ROCKET_LOGO_SVG', Configuration::get('ROCKET_LOGO_SVG')),
            'ROCKETCLASSIC_ACCOUNT' => Tools::getValue('ROCKETCLASSIC_ACCOUNT', Configuration::get('ROCKETCLASSIC_ACCOUNT')),
            'ROCKETCLASSIC_ACCOUNT_TITLE' => Tools::getValue('ROCKETCLASSIC_ACCOUNT_TITLE', Configuration::get('ROCKETCLASSIC_ACCOUNT_TITLE')),
            'ROCKETCLASSIC_ACCOUNT_DESCRIPTION' => Tools::getValue('ROCKETCLASSIC_ACCOUNT_DESCRIPTION', Configuration::get('ROCKETCLASSIC_ACCOUNT_DESCRIPTION')),
            'ROCKETCLASSIC_CATEGORY' => Tools::getValue('ROCKETCLASSIC_CATEGORY', Configuration::get('ROCKETCLASSIC_CATEGORY')),
            'ROCKET_PRODUCT_TABS_TYPE' => Tools::getValue('ROCKET_PRODUCT_TABS_TYPE', Configuration::get('ROCKET_PRODUCT_TABS_TYPE'))
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


    private function getLogoName()
    {
        $shopId = $this->context->shop->id;
        $shopName = $this->context->shop->name;

        $logoName = Tools::link_rewrite($shopName)
            . '-'
            . (int) time()
            . (int) $shopId . '.svg';

        return $logoName;
    }
}
