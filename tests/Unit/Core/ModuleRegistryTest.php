<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Contracts\ModuleInterface;
use App\Modules\Core\Exceptions\MissingModuleDependencyException;
use App\Modules\Core\Services\ModuleRegistry;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    protected ModuleRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ModuleRegistry($this->app);
    }

    public function test_can_register_and_retrieve_module(): void
    {
        $mockModule = $this->createMock(ModuleInterface::class);
        $mockModule->method('getId')->willReturn('test_module');
        $mockModule->method('getPriority')->willReturn(10);
        $mockModule->method('isEnabled')->willReturn(true);

        $this->registry->registerModule($mockModule);

        $this->assertTrue($this->registry->isEnabled('test_module'));
        $this->assertSame($mockModule, $this->registry->find('test_module'));
        $this->assertCount(1, $this->registry->all());
    }

    public function test_modules_are_sorted_by_priority(): void
    {
        $moduleLow = $this->createMock(ModuleInterface::class);
        $moduleLow->method('getId')->willReturn('low_priority');
        $moduleLow->method('getPriority')->willReturn(100);
        $moduleLow->method('isEnabled')->willReturn(true);

        $moduleHigh = $this->createMock(ModuleInterface::class);
        $moduleHigh->method('getId')->willReturn('high_priority');
        $moduleHigh->method('getPriority')->willReturn(5);
        $moduleHigh->method('isEnabled')->willReturn(true);

        $this->registry->registerModule($moduleLow);
        $this->registry->registerModule($moduleHigh);

        $sorted = $this->registry->all()->values();
        $this->assertEquals('high_priority', $sorted[0]->getId());
        $this->assertEquals('low_priority', $sorted[1]->getId());
    }

    public function test_throws_exception_when_dependency_is_missing(): void
    {
        $dependentModule = $this->createMock(ModuleInterface::class);
        $dependentModule->method('getId')->willReturn('dependent');
        $dependentModule->method('getPriority')->willReturn(10);
        $dependentModule->method('getDependencies')->willReturn(['missing_module']);
        $dependentModule->method('isEnabled')->willReturn(true);

        $this->registry->registerModule($dependentModule);

        $this->expectException(MissingModuleDependencyException::class);
        $this->registry->validateDependencies();
    }
}
