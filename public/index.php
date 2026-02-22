<?php

// Composer 오토로더 로드
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../db.php';

use App\Core\ErrorHandler;
use App\Core\Router;

// 중앙 에러 핸들러 등록
set_error_handler([ErrorHandler::class, 'handleError']);
set_exception_handler([ErrorHandler::class, 'handleException']);

// 세션 시작
session_start();

// 라우터 초기화
$router = new Router;

// =======================================
// [라우트 정의] URL action과 실행할 컨트롤러 매핑
// =======================================

// 사용자 관련
$router->add('login', \App\Controllers\UserController::class, 'index');
$router->add('register_user', \App\Controllers\UserController::class, 'register');
$router->add('login_process', \App\Controllers\UserController::class, 'login');
$router->add('logout', \App\Controllers\UserController::class, 'logout');

// 계정과목 관련
$router->add('accounts', \App\Controllers\AccountController::class, 'index');
$router->add('management', \App\Controllers\ManagementController::class, 'index');
$router->add('accounts_advance', \App\Controllers\AccountAdvanceController::class, 'index');
$router->add('account_item_setup', \App\Controllers\AccountSetupController::class, 'index');

// 전표 관련
$router->add('journal_entry', \App\Controllers\JournalController::class, 'index');
$router->add('journal_save', \App\Controllers\JournalController::class, 'save');
$router->add('journal_list', \App\Controllers\ReportController::class, 'journalList');

// 원장 관련
$router->add('ledger', \App\Controllers\LedgerController::class, 'index');

// 대시보드
$router->add('dashboard', \App\Controllers\HomeController::class, 'index');

// 네임스페이스 사용 선언

// ==========================================
// [실행] Dispatch & Error Handling
// ==========================================

// 요청 액션 파악(기본값:dashboard, 비로그인 시: login)
$action = $_GET['action'] ?? 'dashboard';

$router->dispatch($action, $db);
