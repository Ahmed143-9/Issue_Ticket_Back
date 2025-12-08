<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDepartments()
    {
        $departments = [
            'Enterprise Business Solutions',
            'Board Management',
            'Support Stuff',
            'Administration and Human Resources',
            'Finance and Accounts',
            'Business Dev and Operations',
            'Implementation and Support',
            'Technical and Networking Department'
        ];
        
        return response()->json([
            'success' => true,
            'departments' => $departments
        ]);
    }
}