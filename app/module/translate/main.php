<?php
/**
 * @name:	翻译配置
 * @brief:	用于翻译简单信息
 * @author: vincent
 * @create:	2015-3-20
 *
 * @type:	configure
 * @version: 1.0.0
 */
class Module_Translate_Main extends Module_Base_Configure
{
    use singleton_with_get_instance;

    const LANG_EN = 1;
    const LANG_ZH_CN = 2;

    const LPO_EN = 'en';
    const LPO_ZH_CN = 'zh_cn';

    private $_lang = self::LANG_EN;

    private $_lang_property_map = [
        self::LANG_EN => self::LPO_EN,
        self::LANG_ZH_CN => self::LPO_ZH_CN,
    ];

    /**
     * @param string $mod
     * @param string $trans_code
     * @param array|string $trans_data
     * @return string
     */
    static function t($mod, $trans_code, $trans_data)
    {
        return self::get_instance()
            ->_t($mod, $trans_code, $trans_data);
    }

    /**
     * @param int $lang
     * @return mixed
     */
    static function lang($lang)
    {
        return self::get_instance()
            ->_set_current_lang($lang);
    }

    /**
     * @param string $mod
     * @param string $trans_code
     * @param array|string $trans_data
     * @return string
     */
    private function _t($mod, $trans_code, $trans_data)
    {
        $cls = str_replace(
            'Main',
            'Langs_'.ucfirst(strtolower($mod)),
            __CLASS__
        );
        if (class_exists($cls)) {
            $lang = $this->_get_current_lang();
            $property = isset($this->_lang_property_map[$lang])
                ? $this->_lang_property_map[$lang]
                : self::LPO_EN;
            if (property_exists($cls, $property)) {
                $target = $cls::$$property;
                $target = $target[$trans_code];
                return is_array($trans_data)
                    ? vsprintf($target, $trans_data)
                    : sprintf($target, $trans_data);
            }
        }
        return null;
    }

    /**
     * @param int $lang
     * @return $this
     */
    private function _set_current_lang($lang)
    {
        $this->_lang = $lang;
        return $this;
    }

    /**
     * @return int
     */
    private function _get_current_lang()
    {
        return $this->_lang;
    }
}