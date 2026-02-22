<?php

use App\Core\Database;
use App\Database\BaseRepository;
use Mockery as m;

// BaseRepository는 추상 클래스이므로 테스트를 위한 구체 클래스 생성
class TestRepository extends BaseRepository
{
    protected function getTable(): string
    {
        return 'test_table';
    }

    protected function getAllowedColumns(): array
    {
        return ['name', 'created_at'];
    }

    public function hasSystemFlag(): bool
    {
        return $this->systemFlag ?? false;
    }

    public $systemFlag = false;
}

beforeEach(function () {
    $this->dbMock = m::mock(Database::class);
    $this->repo = new TestRepository($this->dbMock);
});

afterEach(function () {
    m::close();
});

test('findById는 올바른 SQL을 생성하고 결과를 반환한다', function () {
    $expectedSql = 'SELECT * FROM test_table WHERE test_tableId = ?';
    $mockStatement = m::mock(\PDOStatement::class);
    $mockStatement->shouldReceive('fetch')->once()->andReturn(['test_tableId' => 1, 'name' => 'test']);

    $this->dbMock->shouldReceive('query')
        ->with($expectedSql, [1])
        ->once()
        ->andReturn($mockStatement);

    $result = $this->repo->findById(1);

    expect($result)->toBe(['test_tableId' => 1, 'name' => 'test']);
});

test('findAll은 조건절을 포함한 SQL을 생성한다', function () {
    $expectedSql = 'SELECT * FROM test_table WHERE userId = ? ORDER BY name';
    $mockStatement = m::mock(\PDOStatement::class);
    $mockStatement->shouldReceive('fetchAll')->once()->andReturn([]);

    $this->dbMock->shouldReceive('query')
        ->with($expectedSql, [100])
        ->once()
        ->andReturn($mockStatement);

    $this->repo->findAll(['userId' => 100], 'name');
});

test('findAll에서 허용되지 않는 컬럼명 사용 시 예외 발생', function () {
    expect(fn () => $this->repo->findAll(['user; DROP TABLE users' => 1]))
        ->toThrow(\InvalidArgumentException::class, '허용되지 않는 컬럼명');
});

test('findAll에서 허용되지 않는 정렬 컬럼 사용 시 예외 발생', function () {
    expect(fn () => $this->repo->findAll([], 'forbidden_col'))
        ->toThrow(\InvalidArgumentException::class, '허용되지 않는 정렬 컬럼');
});

test('create는 INSERT SQL을 생성하고 lastInsertId를 반환한다', function () {
    $data = ['name' => 'new item', 'userId' => 1];
    $expectedSql = 'INSERT INTO test_table (name, userId) VALUES (?, ?)';

    $this->dbMock->shouldReceive('query')
        ->with($expectedSql, ['new item', 1])
        ->once();

    $mockPdo = m::mock(\PDO::class);
    $mockPdo->shouldReceive('lastInsertId')->once()->andReturn('123');

    $this->dbMock->shouldReceive('getPdo')->once()->andReturn($mockPdo);

    $id = $this->repo->create($data);

    expect($id)->toBe(123);
});

test('delete는 hasSystemFlag가 true이고 isSystem이 1인 경우 예외를 던진다', function () {
    $this->repo->systemFlag = true;

    $mockStatement = m::mock(\PDOStatement::class);
    $mockStatement->shouldReceive('fetch')->andReturn(['isSystem' => 1]);

    $this->dbMock->shouldReceive('query')
        ->with('SELECT * FROM test_table WHERE test_tableId = ?', [1])
        ->andReturn($mockStatement);

    expect(fn () => $this->repo->delete(1))
        ->toThrow(\RuntimeException::class, '시스템 레코드는 삭제할 수 없습니다');
});
