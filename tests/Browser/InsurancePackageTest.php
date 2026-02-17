<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\pragati\InsurancePackage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InsurancePackageTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('admin');
    }

    public function test_can_view_packages_list(): void
    {
        $package = InsurancePackage::create([
            'name' => 'Test Package Dusk',
            'description' => 'Test description',
            'price' => 99.99,
            'coverage_amount' => 10000,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'admin@test.com')->first())
                    ->visit('/admin/insurance-packages')
                    ->assertSee('Insurance Packages')
                    ->assertSee('Test Package Dusk');
        });

        $package->delete();
    }

    public function test_can_create_package(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'admin@test.com')->first())
                    ->visit('/admin/insurance-packages/create')
                    ->type('name', 'Dusk Created Package')
                    ->type('description', 'Created via Dusk test')
                    ->type('price', '199.99')
                    ->type('coverage_amount', '25000')
                    ->type('duration_months', '12')
                    ->press('Create Package')
                    ->assertRouteIs('insurance-packages.index')
                    ->assertSee('Dusk Created Package');
        });

        InsurancePackage::where('name', 'Dusk Created Package')->first()?->delete();
    }

    public function test_can_edit_package(): void
    {
        $package = InsurancePackage::create([
            'name' => 'Package To Edit',
            'description' => 'Original description',
            'price' => 99.99,
            'coverage_amount' => 10000,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($package) {
            $browser->loginAs(User::where('email', 'admin@test.com')->first())
                    ->visit("/admin/insurance-packages/{$package->id}/edit")
                    ->type('name', 'Package Edited via Dusk')
                    ->type('price', '299.99')
                    ->press('Update Package')
                    ->assertRouteIs('insurance-packages.index')
                    ->assertSee('Package Edited via Dusk');
        });

        $package->delete();
    }

    public function test_can_toggle_status(): void
    {
        $package = InsurancePackage::create([
            'name' => 'Package To Toggle',
            'description' => 'Test description',
            'price' => 99.99,
            'coverage_amount' => 10000,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($package) {
            $browser->loginAs(User::where('email', 'admin@test.com')->first())
                    ->visit('/admin/insurance-packages')
                    ->click('.fa-power-off')
                    ->assertSee('Package status updated successfully');
        });

        $package->delete();
    }

    public function test_can_delete_package(): void
    {
        $package = InsurancePackage::create([
            'name' => 'Package To Delete',
            'description' => 'Test description',
            'price' => 99.99,
            'coverage_amount' => 10000,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($package) {
            $browser->loginAs(User::where('email', 'admin@test.com')->first())
                    ->visit('/admin/insurance-packages')
                    ->assertSee('Package To Delete')
                    ->press('.fa-trash')
                    ->acceptDialog()
                    ->assertSee('Insurance package deleted successfully');
        });

        $this->assertNull(InsurancePackage::find($package->id));
    }

    public function test_can_view_package_details(): void
    {
        $package = InsurancePackage::create([
            'name' => 'Package To View',
            'description' => 'Detailed description',
            'price' => 149.99,
            'coverage_amount' => 20000,
            'duration_months' => 24,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($package) {
            $browser->loginAs(User::where('email', 'admin@test.com')->first())
                    ->visit("/admin/insurance-packages/{$package->id}")
                    ->assertSee('Package To View')
                    ->assertSee('$149.99')
                    ->assertSee('24 Months');
        });

        $package->delete();
    }
}
