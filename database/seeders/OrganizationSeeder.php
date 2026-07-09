<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Bootstraps EQUIPER as the single v1.0 Organization (tenant), and the
 * four Roles named in the original Discovery document, each with a
 * baseline Permission set matching the F2 acceptance criteria (e.g. a
 * Designer cannot view Customer PII / financial figures).
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->firstOrCreate(
            ['slug' => 'equiper'],
            ['name' => 'Equiper', 'status' => 'active']
        );

        $permissions = [
            'product.view', 'product.manage_category',
            'content.view', 'content.approve', 'content.edit',
            'seo.view', 'seo.approve',
            'campaign.view', 'campaign.manage',
            'dashboard.view_revenue',
            'brand_voice.manage',
            'team.manage',
            'integration.configure',
        ];

        foreach ($permissions as $key) {
            Permission::query()->firstOrCreate(['key' => $key]);
        }

        $roleDefinitions = [
            'owner' => ['name' => 'Owner', 'permissions' => $permissions], // all permissions
            'marketing_manager' => [
                'name' => 'Marketing Manager',
                'permissions' => [
                    'product.view', 'content.view', 'content.approve', 'content.edit',
                    'seo.view', 'campaign.view', 'campaign.manage', 'dashboard.view_revenue',
                ],
            ],
            'seo_specialist' => [
                'name' => 'SEO Specialist',
                'permissions' => ['product.view', 'content.view', 'seo.view', 'seo.approve'],
            ],
            'designer' => [
                'name' => 'Designer',
                // Deliberately excludes dashboard.view_revenue — F2 acceptance criteria.
                'permissions' => ['product.view', 'content.view', 'content.edit'],
            ],
        ];

        foreach ($roleDefinitions as $key => $definition) {
            $role = Role::query()->firstOrCreate(
                ['organization_id' => $org->id, 'key' => $key],
                ['name' => $definition['name'], 'status' => 'active']
            );

            $permissionIds = Permission::query()
                ->whereIn('key', $definition['permissions'])
                ->pluck('id');

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
