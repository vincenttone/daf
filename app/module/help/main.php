<?php
/**
 * @name:	帮助模块
 * @brief:	展示帮助信息的模块
 * @author: vincent
 * @create:	2014-7-30
 * @update:	2014-7-30
 *
 * @type:   system
 * @register: web
 * @version: 1.0.1
 */
class Module_Help_Main extends Module_Base_System
{
    /**
     * @return array
     */
    static function register_router()
    {
        return [
            'index' => [
                'Module_Help_Action', 'index_action', 'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_NAME => '帮助信息',
                    Const_DataAccess::URL_WEIGHT => 99,
                    Const_DataAccess::URL_CATALOG => Const_DataAccess::CATALOG_URL_HELP,
                ],
            ],
            'make-index' => [
                'Module_Help_Action', 'make_index_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_DOC_ADMIN,
                ]
            ],
            'development' => [
                'Module_Help_Action', 'dev_action', 'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_NAME => '开发者',
                    Const_DataAccess::URL_WEIGHT => 93,
                    Const_DataAccess::URL_CATALOG => Const_DataAccess::CATALOG_URL_HELP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_INTERNAL_DOC,
                ]
            ],
            'make-development' => [
                'Module_Help_Action', 'make_dev_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_DOC_ADMIN,
                ]
            ],
            'todo' => [
                'Module_Help_Action', 'todo_action', 'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_NAME => 'TODO',
                    Const_DataAccess::URL_WEIGHT => 95,
                    Const_DataAccess::URL_CATALOG => Const_DataAccess::CATALOG_URL_HELP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_INTERNAL_DOC,
                ]
            ],
            'make-todo' => [
                'Module_Help_Action', 'make_todo_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_DOC_ADMIN,
                ]
            ],
            'api/internal' => [
                'Module_Help_Action', 'internal_api_action', 'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_NAME => '内部API',
                    Const_DataAccess::URL_WEIGHT => 90,
                    Const_DataAccess::URL_CATALOG => Const_DataAccess::CATALOG_URL_HELP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_INTERNAL_DOC,
                ]
            ],
            'api/make-internal' => [
                'Module_Help_Action', 'make_internal_api_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_DOC_ADMIN,
                ]
            ],
            'api/open' => [
                'Module_Help_Action', 'open_api_action', 'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_NAME => '开放API',
                    Const_DataAccess::URL_WEIGHT => 97,
                    Const_DataAccess::URL_CATALOG => Const_DataAccess::CATALOG_URL_HELP,
                ]
            ],
            'api/make-open' => [
                'Module_Help_Action', 'make_open_api_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_DOC_ADMIN,
                ]
            ],
        ];
    }
}