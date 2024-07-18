<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::paginate(10);
        return view('csv', compact('employees'));
    }

    public function exportCSV()
    {
        $filename = 'employee-data.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($handle, [
                'First Name',
                'Last Name',
                'Email',
                'Phone Number',
                'Date of Birth',
                'Gender',
                'Address',
                'Salary',
                'Skills'
            ]);

             // Fetch and process data in chunks
            Employee::chunk(25, function ($employees) use ($handle) {
                foreach ($employees as $employee) {
             // Extract data from each employee.
                    $data = [
                        isset($employee->first_name)? $employee->first_name : '',
                        isset($employee->last_name)? $employee->last_name : '',
                        isset($employee->email)? $employee->email : '',
                        isset($employee->phone)? $employee->phone : '',
                        isset($employee->date_of_birth)? $employee->date_of_birth : '',
                        isset($employee->gender)? $employee->gender : '',
                        isset($employee->address)? $employee->address : '',
                        isset($employee->basic_salary)? $employee->basic_salary : '',
                        isset($employee->skills)? $employee->skills : '',
                    ];

             // Write data to a CSV file.
                    fputcsv($handle, $data);
                }
            });

            // Close CSV file handle
            fclose($handle);
        }, 200, $headers);
    }

    public function importCSV(Request $request)
    {
        $request->validate([
            'import_csv' => 'required|mimes:csv',
        ]);
        //read csv file and skip data
        $file = $request->file('import_csv');
        $handle = fopen($file->path(), 'r');

        //skip the header row
        fgetcsv($handle);

        $chunksize = 25;
        while(!feof($handle))
        {
            $chunkdata = [];

            for($i = 0; $i<$chunksize; $i++)
            {
                $data = fgetcsv($handle);
                if($data === false)
                {
                    break;
                }
                $chunkdata[] = $data;
            }

            $this->getchunkdata($chunkdata);
        }
        fclose($handle);

        return redirect()->route('employee.create')->with('success', 'Data has been added successfully.');
    }

    public function getchunkdata($chunkdata)
    {
        foreach($chunkdata as $column){
            $firstname = $column[0];
            $lastname = $column[1];
            $email = $column[2];
            $phoneNumber = $column[3];
            $dateOfBirth = $column[4];
            $gender = $column[5];
            $address = $column[6];
            $skill = json_encode([$column[7]]);
            $sallary = $column[8];

            //create new employee
            $employee = new Employee();
            $employee->first_name = $firstname;
            $employee->last_name = $lastname;
            $employee->email = $email;
            $employee->phone = $phoneNumber;
            $employee->date_of_birth = $dateOfBirth;
            $employee->gender = $gender;
            $employee->address = $address;
            $employee->skills = $skill;
            $employee->basic_salary = $sallary;
            $employee->save();
        }
    }

}
