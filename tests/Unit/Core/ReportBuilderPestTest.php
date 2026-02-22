<?php

use App\Core\Database;
use App\Core\ReportBuilder;
use Mockery as m;

beforeEach(function () {
    $this->dbMock = m::mock(Database::class);
    $this->builder = new ReportBuilder($this->dbMock);
});

afterEach(function () {
    m::close();
});

test('기본 SELECT 쿼리 빌드', function () {
    $sql = $this->builder
        ->select(['transactionId', 'description'])
        ->from('transactions')
        ->build();

    expect($sql)->toBe('SELECT transactionId, description FROM transactions');
});

test('별칭을 포함한 FROM 및 JOIN 쿼리 빌드', function () {
    $sql = $this->builder
        ->select(['t.transactionId', 'je.debitAmount'])
        ->from('transactions', 't')
        ->join('INNER', 'journalEntries', 'je', 'je.transactionId = t.transactionId')
        ->build();

    expect($sql)->toBe('SELECT t.transactionId, je.debitAmount FROM transactions t INNER JOIN journalEntries je ON je.transactionId = t.transactionId');
});

test('WHERE 조건 및 파라미터 빌드', function () {
    $this->builder
        ->from('accounts')
        ->where('userId', 1)
        ->where('accountType', 'ASSET');

    $sql = $this->builder->build();

    expect($sql)->toBe('SELECT * FROM accounts WHERE userId = ? AND accountType = ?');

    // params는 private이므로 실제 실행 시 db->query로 전달됨을 검증해야 함
    $this->dbMock->shouldReceive('query')
        ->with($sql, [1, 'ASSET'])
        ->once()
        ->andReturn(m::mock(\PDOStatement::class, ['fetchAll' => []]));

    $this->builder->get();
});

test('허용되지 않는 테이블명 사용 시 예외 발생', function () {
    expect(fn () => $this->builder->from('forbidden_table'))
        ->toThrow(\InvalidArgumentException::class, '허용되지 않는 테이블명');
});

test('허용되지 않는 SELECT 표현식 사용 시 예외 발생', function () {
    expect(fn () => $this->builder->select(['id; DROP TABLE users;']))
        ->toThrow(\InvalidArgumentException::class, '허용되지 않는 SELECT 표현식');
});

test('ORDER BY 및 LIMIT 빌드', function () {
    $sql = $this->builder
        ->from('transactions')
        ->orderBy('transactionDate', 'DESC')
        ->limit(10)
        ->build();

    expect($sql)->toBe('SELECT * FROM transactions ORDER BY transactionDate DESC LIMIT 10');
});

test('WHERE IN 조건 빌드', function () {
    $sql = $this->builder
        ->from('accounts')
        ->whereIn('accountId', [1, 2, 3])
        ->build();

    expect($sql)->toBe('SELECT * FROM accounts WHERE accountId IN (?, ?, ?)');
});

test('WHERE BETWEEN 조건 빌드', function () {
    $sql = $this->builder
        ->from('transactions')
        ->whereBetween('transactionDate', '2026-01-01', '2026-01-31')
        ->build();

    expect($sql)->toBe('SELECT * FROM transactions WHERE transactionDate BETWEEN ? AND ?');
});
