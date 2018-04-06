<?php

use Illuminate\Database\Seeder;
use App\Profile;

class ProfilesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $profiles = [
            [
              'id' => 1,
              'user_id' => 1,
              'sur_name' => 'Travel',
              'first_name' => 'Portal',
              'other_name' => 'Admin',
              'phone_number' => '09090909090',
              'address'    => 'Travel Portal at that place'
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'sur_name' => 'First',
                'first_name' => 'Portal',
                'other_name' => 'Agent',
                'phone_number' => '09090111111',
                'address'    => 'Travel portal agent shop at that place'
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'sur_name' => 'First',
                'first_name' => 'Test',
                'other_name' => 'Customer',
                'phone_number' => '09090222222',
                'address'    => 'First test customer'
            ],
            [
                'id' => 4,
                'user_id' => 4,
                'sur_name' => 'Ogunsakin',
                'first_name' => 'Damilola',
                'other_name' => 'Olamide',
                'phone_number' => '09090444444',
                'address'    => 'everywhere in lagos'
            ]
        ];

        foreach($profiles as $serial => $profile){
           Profile::create($profile);
        }
    }
}
