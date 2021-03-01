<?php
/**
 * NOTICE OF LICENSE.
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

    protected $errors = array();

    public function __construct()
    {
        $this->name = 'prestarockettheme';
        $this->author = 'Prestarocket';
        $this->version = '1.0.0';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Prestarocket Classic Rocket Theme');
        $this->description = $this->l('Prestarocket Classic Rocket Theme');

        $this->ps_versions_compliancy = array(
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        );

        $this->imgUploadFolder = _PS_IMG_DIR_.$this->name.DIRECTORY_SEPARATOR;
    }

    public function install()
    {
        Configuration::updateValue('ROCKETTHEME_PRODUCT_TABS_TYPE', 1);

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
        Configuration::deleteByName('ROCKETTHEME_ACCOUNT_FILE');
        Configuration::deleteByName('ROCKETTHEME_ACCOUNT_FILE_WIDTH');
        Configuration::deleteByName('ROCKETTHEME_ACCOUNT_FILE_HEIGHT');
        Configuration::deleteByName('ROCKETTHEME_ACCOUNT_TITLE');
        Configuration::deleteByName('ROCKETTHEME_ACCOUNT_DESCRIPTION');
        Configuration::deleteByName('ROCKETTHEME_CATEGORY');
        Configuration::deleteByName('ROCKETTHEME_PRODUCT_TABS_TYPE');
        Configuration::deleteByName('ROCKETTHEME_LOGO_SVG_FILE');
        Configuration::deleteByName('ROCKETTHEME_LOGO_SVG_FILE_WIDTH');
        Configuration::deleteByName('ROCKETTHEME_LOGO_SVG_FILE_HEIGHT');

        return parent::uninstall();
    }

    public function hookActionFrontControllerSetVariables()
    {
        $account_file = false;
        if (Configuration::get('ROCKETTHEME_ACCOUNT_FILE')) {
            $account_file = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder.Configuration::get('ROCKETTHEME_ACCOUNT_FILE')));
            $account_file .= '?v='.Configuration::get('ROCKETTHEME_UPLOAD_DATE');
        }

        $svg_logo_url = false;
        if (Configuration::get('ROCKETTHEME_LOGO_SVG_FILE')) {
            $svg_logo_url = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder.Configuration::get('ROCKETTHEME_LOGO_SVG_FILE')));
            $svg_logo_url .= '?v='.Configuration::get('ROCKETTHEME_UPLOAD_DATE');
        }

        $product_layout = Configuration::get('ROCKETTHEME_PRODUCT_TABS_TYPE');
        if (!$product_layout) {
            $product_layout = 'tabs';
        }

        return array(
            'logo' => array(
                'url' => $svg_logo_url,
                'width' => Configuration::get('ROCKETTHEME_LOGO_SVG_FILE_WIDTH'),
                'height' => Configuration::get('ROCKETTHEME_LOGO_SVG_FILE_HEIGHT'),
            ),
            'account' => array(
                'title_account' => Configuration::get('ROCKETTHEME_ACCOUNT_TITLE'),
                'description_account' => Configuration::get('ROCKETTHEME_ACCOUNT_DESCRIPTION'),
                'image' => array(
                    'url' => $account_file,
                    'width' => Configuration::get('ROCKETTHEME_ACCOUNT_FILE_WIDTH'),
                    'height' => Configuration::get('ROCKETTHEME_ACCOUNT_FILE_HEIGHT'),
                ),
            ),
            'category' => array(
                'category_switch' => Configuration::get('ROCKETTHEME_CATEGORY'),
            ),
            'product' => array(
                'product_layout' => $product_layout,
            ),
            'cccjs_version' => Configuration::get('PS_CCCJS_VERSION'),
            'ccccss_version' => Configuration::get('PS_CCCCSS_VERSION'),
        );
    }

    public function getContent()
    {
        $this->postProcess();
        if (count($this->errors)) {
            $this->html = $this->displayError($this->errors);
        }

        return $this->html.$this->renderForm();
    }

    protected function renderForm()
    {
        $account_file = false;
        if (Configuration::get('ROCKETTHEME_ACCOUNT_FILE')) {
            $account_file = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder.Configuration::get('ROCKETTHEME_ACCOUNT_FILE')));
            $account_file .= '?v='.Configuration::get('ROCKETTHEME_UPLOAD_DATE');
            $deleteAccountFile = $this->context->link->getAdminLink('AdminModules', true, array(), array(
                'configure' => $this->name,
                'deleteImg' => 'ROCKETTHEME_ACCOUNT_FILE',
            ));
        }

        $logo_svg = false;
        if (Configuration::get('ROCKETTHEME_LOGO_SVG_FILE')) {
            $logo_svg = $this->context->link->getMediaLink(Media::getMediaPath($this->imgUploadFolder.Configuration::get('ROCKETTHEME_LOGO_SVG_FILE')));
            $logo_svg .= '?v='.Configuration::get('ROCKETTHEME_UPLOAD_DATE');
            $deleteLogoSvg = $this->context->link->getAdminLink('AdminModules', true, array(), array(
                'configure' => $this->name,
                'deleteImg' => 'ROCKETTHEME_LOGO_SVG_FILE',
            ));
        }

        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Classic rocket theme configuration'),
            ),
            'tabs' => array(
                'svg' => $this->l('Logo SVG'),
                'account' => $this->l('my account'),
                'category' => $this->l('categories'),
                'product' => $this->l('Product page'),
            ),
            'input' => array(
                // Logo tab
                'svg_logo_preview' => array(
                    'type' => 'html',
                    'tab' => 'svg',
                    'name' => 'ROCKETTHEME_LOGO_SVG_FILE_PREVIEW',
                    'html_content' => ($logo_svg ? '<div class="col-lg-9 col-lg-offset-3"><img src="'.$logo_svg.'" alt="ROCKETTHEME_LOGO_SVG_FILE_PREVIEW" width="200" height="auto"><a href="'.$deleteLogoSvg.'">'.$this->l('Supprimer').'</a></div>' : ''),
                ),
                'svg_file' => array(
                    'type' => 'file',
                    'label' => $this->l('Logo SVG'),
                    'name' => 'ROCKETTHEME_LOGO_SVG_FILE',
                    'desc' => $this->l('Upload a svg logo for your shop'),
                    'tab' => 'svg',
                    'required' => false,
                ),
                // Account tab
                'account_image_preview' => array(
                    'type' => 'html',
                    'name' => 'ROCKETTHEME_ACCOUNT_FILE_PREVIEW',
                    'tab' => 'account',
                    'html_content' => ($account_file ? '<div class="col-lg-9 col-lg-offset-3"><img src="'.$account_file.'" alt="ROCKETTHEME_ACCOUNT_FILE_PREVIEW" width="200" height="auto"><a href="'.$deleteAccountFile.'">'.$this->l('Supprimer').'</a></div>' : ''),
                ),
                'account_image' => array(
                    'type' => 'file',
                    'label' => $this->l('Account Picture'),
                    'name' => 'ROCKETTHEME_ACCOUNT_FILE',
                    'tab' => 'account',
                    'required' => false,
                ),
                'account_title' => array(
                    'type' => 'text',
                    'label' => $this->l('Account title'),
                    'name' => 'ROCKETTHEME_ACCOUNT_TITLE',
                    'tab' => 'account',
                    'required' => false,
                ),
                'account_description' => array(
                    'type' => 'textarea',
                    'label' => $this->trans('Description', array(), 'Modules.rocketCustomtext.Admin'),
                    'name' => 'ROCKETTHEME_ACCOUNT_DESCRIPTION',
                    'tab' => 'account',
                    'class' => 'rte',
                    'autoload_rte' => true,
                ),
                // Category tab
                'category_switch' => array(
                    'type' => 'switch',
                    'label' => $this->l('Show the sub_categories'),
                    'name' => 'ROCKETTHEME_CATEGORY',
                    'desc' => $this->l('Please select if you want to show the sub-categories'),
                    'required' => false,
                    'tab' => 'category',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                // Product tab
                array(
                    'type' => 'select',
                    'tab' => 'product',
                    'label' => $this->l('Product tabs layout'),
                    'name' => 'ROCKETTHEME_PRODUCT_TABS_TYPE',
                    'desc' => $this->l('How to display product tabs on product page'),
                    'required' => false,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 'tabs',
                                'name' => 'Tabs'
                            ),
                            array(
                                'id' => 'collapse',
                                'name' => 'Collapse'
                            ),
                            array(
                                'id' => 'columns',
                                'name' => 'In columns'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($fieldsForm);
    }

    protected function getConfigFieldsValues()
    {
        return array(
            'ROCKETTHEME_ACCOUNT_TITLE' => Tools::getValue('ROCKETTHEME_ACCOUNT_TITLE', Configuration::get('ROCKETTHEME_ACCOUNT_TITLE')),
            'ROCKETTHEME_ACCOUNT_DESCRIPTION' => Tools::getValue('ROCKETTHEME_ACCOUNT_DESCRIPTION', Configuration::get('ROCKETTHEME_ACCOUNT_DESCRIPTION')),
            'ROCKETTHEME_CATEGORY' => Tools::getValue('ROCKETTHEME_CATEGORY', Configuration::get('ROCKETTHEME_CATEGORY')),
            'ROCKETTHEME_PRODUCT_TABS_TYPE' => Tools::getValue('ROCKETTHEME_PRODUCT_TABS_TYPE', Configuration::get('ROCKETTHEME_PRODUCT_TABS_TYPE')),
        );
    }

    protected function postProcess()
    {
        if (Tools::isSubmit('deleteImg')) {
            $img = Tools::getValue('deleteImg');
            $image_name = Configuration::get($img);
            if (file_exists($this->imgUploadFolder.$image_name)) {
                unlink($this->imgUploadFolder.$image_name);

                Configuration::updateValue($img, '');
                Configuration::updateValue($img.'_WIDTH', 0);
                Configuration::updateValue($img.'_HEIGHT', 0);

                $this->html .= $this->displayConfirmation($this->l('Image deleted !'));
            }
        }

        if (Tools::isSubmit('submit'.$this->name)) {
            Configuration::updateValue('ROCKETTHEME_PRODUCT_TABS_TYPE', Tools::getValue('ROCKETTHEME_PRODUCT_TABS_TYPE'));
            Configuration::updateValue('ROCKETTHEME_ACCOUNT_TITLE', Tools::getValue('ROCKETTHEME_ACCOUNT_TITLE'));
            Configuration::updateValue('ROCKETTHEME_ACCOUNT_DESCRIPTION', Tools::getValue('ROCKETTHEME_ACCOUNT_DESCRIPTION'));
            Configuration::updateValue('ROCKETTHEME_CATEGORY', Tools::getValue('ROCKETCLASSIC_CATEGORY'));

            $this->uploadLogoSvg();
            $this->uploadAccountFile();
            Configuration::updateValue('ROCKETTHEME_UPLOAD_DATE', time());

            $this->html .= $this->displayConfirmation($this->l('Configuration saved !'));
        }

        return true;
    }

    protected function uploadAccountFile()
    {
        $errors_file_upload = array();
        $account_file = Tools::link_rewrite($this->context->shop->name).'-account-bg';
        if (isset($_FILES['ROCKETTHEME_ACCOUNT_FILE']) && (UPLOAD_ERR_NO_FILE !== $_FILES['ROCKETTHEME_ACCOUNT_FILE']['error'])) {
            if (!in_array($_FILES['ROCKETTHEME_ACCOUNT_FILE']['type'], array('image/jpeg', 'image/png', 'image/webm'))) {
                $errors_file_upload[] = $this->l('Wrong! Uploaded file is not a jpg, png or webp file.');
            } elseif ($_FILES['ROCKETTHEME_ACCOUNT_FILE']['error']) {
                $errors_file_upload[] = $this->getUploadErrorMessage($_FILES['ROCKETTHEME_ACCOUNT_FILE']['error']);
            }

            list($width, $height, $type) = getimagesize($_FILES['ROCKETTHEME_ACCOUNT_FILE']['tmp_name']);
            $ext = image_type_to_extension($type);
            if (!move_uploaded_file($_FILES['ROCKETTHEME_ACCOUNT_FILE']['tmp_name'], $this->imgUploadFolder.$account_file.$ext)) {
                $errors_file_upload[] = $this->l('Wrong! The file has not been uploaded.');
            }

            if (empty($errors_file_upload)) {
                Configuration::updateValue('ROCKETTHEME_ACCOUNT_FILE', $account_file.$ext);
                Configuration::updateValue('ROCKETTHEME_ACCOUNT_FILE_WIDTH', $width);
                Configuration::updateValue('ROCKETTHEME_ACCOUNT_FILE_HEIGHT', $height);
            } else {
                $this->errors = array_merge($this->errors, $errors_file_upload);
            }
        }
    }

    protected function uploadLogoSvg()
    {
        $errors_svg_upload = array();
        $logo_name = Tools::link_rewrite($this->context->shop->name).'-logo.svg';
        if (isset($_FILES['ROCKETTHEME_LOGO_SVG_FILE']) && (UPLOAD_ERR_NO_FILE !== $_FILES['ROCKETTHEME_LOGO_SVG_FILE']['error'])) {
            if ('image/svg+xml' !== $_FILES['ROCKETTHEME_LOGO_SVG_FILE']['type']) {
                $errors_svg_upload[] = $this->l('Wrong! Uploaded file is not a svg file.');
            } elseif ($_FILES['ROCKETTHEME_LOGO_SVG_FILE']['error']) {
                $errors_svg_upload[] = $this->getUploadErrorMessage($_FILES['ROCKETTHEME_LOGO_SVG_FILE']['error']);
            } elseif (!move_uploaded_file($_FILES['ROCKETTHEME_LOGO_SVG_FILE']['tmp_name'], $this->imgUploadFolder.$logo_name)) {
                $errors_svg_upload[] = $this->l('Wrong! The file has not been uploaded.');
            }

            if (empty($errors_svg_upload)) {
                Configuration::updateValue('ROCKETTHEME_LOGO_SVG_FILE', $logo_name);
                $this->updateImageSize();
            } else {
                $this->errors = array_merge($this->errors, $errors_svg_upload);
            }
        }
    }

    private function updateImageSize()
    {
        $width = false;
        $height = false;
        $logo_name = Configuration::get('ROCKETTHEME_LOGO_SVG_FILE');

        $file = $this->imgUploadFolder.$logo_name;
        if ($file) {
            $xml = file_get_contents($file);
            $xmlget = simplexml_load_string($xml);
            $xmlattributes = $xmlget->attributes();
            $xmlwidth = (string) $xmlattributes->width;
            $xmlheight = (string) $xmlattributes->height;

            if (strpos($xmlwidth, 'px')) {
                $width = (int) str_replace('px', '', $xmlwidth);
            }

            if (strpos($xmlheight, 'px')) {
                $height = (int) str_replace('px', '', $xmlheight);
            }
        }

        Configuration::updateValue('ROCKETTHEME_LOGO_SVG_FILE_HEIGHT', $height);
        Configuration::updateValue('ROCKETTHEME_LOGO_SVG_FILE_WIDTH', $width);
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
