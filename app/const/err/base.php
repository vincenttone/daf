<?php
/**
 * @file base.php
 * @author vincent
 * @date 2013-12-8
 * @description
 *  相关命令号类
 **/
class Const_Err_Base{
	
	// 系统错误号区间 0 ~ 99

    // 底层错误 0 ~ 50
	const ERR_OK = 0;					// 一切正常
	const ERR_SYSTEM = 1;				// 系统错误
    const ERR_FILE_OPEN = 2;			// 打开文件错误
    const ERR_FILE_READ = 3;			// 读文件错误
    const ERR_FILE_WRITE = 4;			// 写文件错误
    const ERR_EMPTY_PARAM = 5;			// 未传递参数
    const ERR_DIRECTORY_CREATE = 6;		// 目录创建
    const ERR_DIRECTORY_DELETE = 7;		// 目录删除
    const ERR_DIRECTORY_EMPTY = 8;		// 空目录
    const ERR_INVALID_PARAM = 9;		// 错误的参数传递
    const ERR_FILE_NOT_EXISTS = 10;
    const ERR_DIRECTORY_NOT_EXISTS = 11;
    const ERR_FILE_DELETE = 12;
    const ERR_DATA_FORMAT = 13;
    const ERR_FORK_FAIL = 14;
    const ERR_EMPTY_FILE = 15;			// 空文件
    const ERR_EMPTY_INPUT = 16;			// 空的输入
    const ERR_NO_PERM = 17; 			// 无权限
    const ERR_UNEXPECT_RETURN = 18;		// 不符合预期的返回
    // 框架等级  51 ~ 100
    const ERR_NO_CONFIG_PATH = 51;		// 未找到相关配置路径
    const ERR_NO_CONFIG_FILE = 52;		// 未找到相关配置文件
    const ERR_CONFIG_MISSING = 53;		// 缺少相关配置项
    const ERR_TITLE_INVALID  = 54;      // 文件头不正确
	// 各业务错误号区间
}
