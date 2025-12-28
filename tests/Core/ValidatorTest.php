<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredRule(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($v->passes());
    }

    public function testEmailRule(): void
    {
        $v = Validator::make(['email' => 'invalid'], ['email' => 'email']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['email' => 'john@example.com'], ['email' => 'email']);
        $this->assertTrue($v->passes());
    }

    public function testMinMaxRules(): void
    {
        // String length
        $v = Validator::make(['name' => 'Jo'], ['name' => 'min:3']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['name' => 'John'], ['name' => 'min:3']);
        $this->assertTrue($v->passes());
        
        $v = Validator::make(['name' => 'John Doe Smith'], ['name' => 'max:10']);
        $this->assertTrue($v->fails());
    }

    public function testIntegerRule(): void
    {
        $v = Validator::make(['age' => 'abc'], ['age' => 'integer']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['age' => 25], ['age' => 'integer']);
        $this->assertTrue($v->passes());
        
        $v = Validator::make(['age' => '25'], ['age' => 'integer']);
        $this->assertTrue($v->passes());
    }

    public function testInRule(): void
    {
        $v = Validator::make(['role' => 'hacker'], ['role' => 'in:admin,user,guest']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['role' => 'admin'], ['role' => 'in:admin,user,guest']);
        $this->assertTrue($v->passes());
    }

    public function testNullableRule(): void
    {
        $v = Validator::make(['age' => null], ['age' => 'nullable|integer']);
        $this->assertTrue($v->passes());
        
        $v = Validator::make(['age' => 25], ['age' => 'nullable|integer']);
        $this->assertTrue($v->passes());
    }

    public function testBetweenRule(): void
    {
        $v = Validator::make(['age' => 15], ['age' => 'between:18,65']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['age' => 25], ['age' => 'between:18,65']);
        $this->assertTrue($v->passes());
    }

    public function testConfirmedRule(): void
    {
        $v = Validator::make([
            'password' => 'secret',
            'password_confirmation' => 'different'
        ], ['password' => 'confirmed']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make([
            'password' => 'secret',
            'password_confirmation' => 'secret'
        ], ['password' => 'confirmed']);
        $this->assertTrue($v->passes());
    }

    public function testMultipleRules(): void
    {
        $v = Validator::make([
            'name' => 'Jo',
            'email' => 'invalid',
            'age' => 15,
        ], [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'age' => 'integer|min:18',
        ]);
        
        $this->assertTrue($v->fails());
        $errors = $v->errors();
        
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
    }

    public function testValidatedMethod(): void
    {
        $v = Validator::make([
            'name' => 'John',
            'email' => 'john@example.com',
            'extra' => 'ignored',
        ], [
            'name' => 'required',
            'email' => 'email',
        ]);
        
        $validated = $v->validated();
        
        $this->assertArrayHasKey('name', $validated);
        $this->assertArrayHasKey('email', $validated);
        $this->assertArrayNotHasKey('extra', $validated);
    }

    public function testCustomMessages(): void
    {
        $v = Validator::make(
            ['name' => ''],
            ['name' => 'required'],
            ['name.required' => 'Please enter your name']
        );
        
        $this->assertEquals('Please enter your name', $v->firstError());
    }

    public function testUrlRule(): void
    {
        $v = Validator::make(['site' => 'not-a-url'], ['site' => 'url']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['site' => 'https://example.com'], ['site' => 'url']);
        $this->assertTrue($v->passes());
    }

    public function testAlphaNumRule(): void
    {
        $v = Validator::make(['code' => 'abc-123'], ['code' => 'alpha_num']);
        $this->assertTrue($v->fails());
        
        $v = Validator::make(['code' => 'abc123'], ['code' => 'alpha_num']);
        $this->assertTrue($v->passes());
    }
}
