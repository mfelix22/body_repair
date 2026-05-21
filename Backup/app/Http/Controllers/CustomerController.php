<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('customers')) {
            return PermissionHelper::denyAccess('customers', 'view');
        }

        $customers = Customer::withCount(['workOrders', 'invoices'])->get();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('customers')) {
            return PermissionHelper::denyAccess('customers', 'create');
        }

        $nextCode = Customer::generateNextCode();
        return view('customers.create', compact('nextCode'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('customers')) {
            return PermissionHelper::denyAccess('customers', 'create');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = Customer::generateNextCode();
        $validated['is_active'] = $request->has('is_active');

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully!');
    }

    public function show(Customer $customer)
    {
        if (!PermissionHelper::canView('customers')) {
            return PermissionHelper::denyAccess('customers', 'view');
        }

        $customer->load(['workOrders.items', 'invoices']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        if (!PermissionHelper::canUpdate('customers')) {
            return PermissionHelper::denyAccess('customers', 'update');
        }

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        if (!PermissionHelper::canUpdate('customers')) {
            return PermissionHelper::denyAccess('customers', 'update');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
    }

    public function destroy(Customer $customer)
    {
        if (!PermissionHelper::canDelete('customers')) {
            return PermissionHelper::denyAccess('customers', 'delete');
        }

        try {
            $customer->delete();
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', 'Cannot delete customer. They may have work orders or invoices.');
        }
    }

    public function showImportForm()
    {
        if (!PermissionHelper::canCreate('customers')) {
            return PermissionHelper::denyAccess('customers', 'create');
        }

        return view('customers.import');
    }

    public function processImport(Request $request)
    {
        if (!PermissionHelper::canCreate('customers')) {
            return PermissionHelper::denyAccess('customers', 'create');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $importedCustomers = 0;
            $importedVehicles = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                // Skip header row
                if ($index === 0) {
                    continue;
                }

                // Skip empty rows
                if (empty($row[2]) && empty($row[3]) && empty($row[4])) {
                    continue;
                }

                try {
                    // Extract customer data
                    // Column C (index 2): Customer name
                    // Column D (index 3): Phone No.
                    // Column E (index 4): Address
                    $customerName = trim($row[2] ?? '');
                    $phone = trim($row[3] ?? '');
                    $address = trim($row[4] ?? '');

                    if (empty($customerName)) {
                        $errors[] = "Row " . ($index + 1) . ": Customer name is required";
                        continue;
                    }

                    // Check if customer already exists by name and phone
                    $customer = Customer::where('name', $customerName)
                        ->where('phone', $phone)
                        ->first();

                    if (!$customer) {
                        // Create new customer
                        $customer = Customer::create([
                            'name' => $customerName,
                            'phone' => $phone,
                            'address' => $address,
                            'is_active' => true,
                        ]);
                        $importedCustomers++;
                    }

                    // Extract vehicle data
                    // Column F (index 5): Car Model (e.g., "Mercedes Benz E300")
                    // Column G (index 6): No. Rangka (Chassis Number)
                    // Column H (index 7): No. Polisi (Plate Number)
                    $carModel = trim($row[5] ?? '');
                    $chassisNo = trim($row[6] ?? '');
                    $plateNumber = trim($row[7] ?? '');

                    if (!empty($carModel) || !empty($chassisNo) || !empty($plateNumber)) {
                        // Split car model into brand and model
                        // Example: "Mercedes Benz E300" -> brand: "Mercedes Benz", model: "E300"
                        $brand = '';
                        $model = '';
                        
                        if (!empty($carModel)) {
                            // Try to split by common brand patterns
                            $carModelParts = explode(' ', $carModel);
                            if (count($carModelParts) >= 2) {
                                // Check if it's a two-word brand (Mercedes Benz, Land Rover, etc.)
                                if (count($carModelParts) >= 3 && 
                                    in_array($carModelParts[0], ['Mercedes', 'Land', 'Alfa', 'Aston'])) {
                                    $brand = $carModelParts[0] . ' ' . $carModelParts[1];
                                    $model = implode(' ', array_slice($carModelParts, 2));
                                } else {
                                    $brand = $carModelParts[0];
                                    $model = implode(' ', array_slice($carModelParts, 1));
                                }
                            } else {
                                $brand = $carModel;
                            }
                        }

                        // Check if vehicle already exists by plate number or chassis number
                        $vehicleExists = Vehicle::where('customer_id', $customer->id)
                            ->where(function($query) use ($plateNumber, $chassisNo) {
                                if (!empty($plateNumber)) {
                                    $query->orWhere('plate_number', $plateNumber);
                                }
                                if (!empty($chassisNo)) {
                                    $query->orWhere('chasis_no', $chassisNo);
                                }
                            })
                            ->exists();

                        if (!$vehicleExists) {
                            Vehicle::create([
                                'customer_id' => $customer->id,
                                'plate_number' => $plateNumber,
                                'brand' => $brand,
                                'model' => $model,
                                'chasis_no' => $chassisNo,
                                'year' => null, // Leave empty for warehouse user to update
                                'color' => null, // Leave empty for warehouse user to update
                                'is_active' => true,
                            ]);
                            $importedVehicles++;
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                    Log::error("Import error on row " . ($index + 1), ['error' => $e->getMessage()]);
                }
            }

            DB::commit();

            $message = "Import completed! Customers: {$importedCustomers}, Vehicles: {$importedVehicles}";
            
            if (!empty($errors)) {
                $message .= " | Errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (and " . (count($errors) - 5) . " more)";
                }
            }

            return redirect()->route('customers.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export customers and vehicles to Excel file
     */
    public function export()
    {
        if (!PermissionHelper::canView('customers')) {
            return PermissionHelper::denyAccess('customers', 'view');
        }

        try {
            $spreadsheet = new Spreadsheet();
            
            // ===== CUSTOMERS SHEET =====
            $customersSheet = $spreadsheet->getActiveSheet();
            $customersSheet->setTitle('Customers');
            
            // Set header row
            $customersSheet->setCellValue('A1', 'No');
            $customersSheet->setCellValue('B1', 'Code');
            $customersSheet->setCellValue('C1', 'Name');
            $customersSheet->setCellValue('D1', 'Phone');
            $customersSheet->setCellValue('E1', 'Email');
            $customersSheet->setCellValue('F1', 'Address');
            $customersSheet->setCellValue('G1', 'Work Orders');
            $customersSheet->setCellValue('H1', 'Invoices');
            $customersSheet->setCellValue('I1', 'Status');
            
            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $customersSheet->getStyle('A1:I1')->applyFromArray($headerStyle);
            
            // Fetch customers with counts
            $customers = Customer::withCount(['workOrders', 'invoices'])->orderBy('name')->get();
            
            // Populate data rows
            $row = 2;
            foreach ($customers as $index => $customer) {
                $customersSheet->setCellValue('A' . $row, $index + 1);
                $customersSheet->setCellValue('B' . $row, $customer->code);
                $customersSheet->setCellValue('C' . $row, $customer->name);
                $customersSheet->setCellValue('D' . $row, $customer->phone ?? '-');
                $customersSheet->setCellValue('E' . $row, $customer->email ?? '-');
                $customersSheet->setCellValue('F' . $row, $customer->address ?? '-');
                $customersSheet->setCellValue('G' . $row, $customer->work_orders_count);
                $customersSheet->setCellValue('H' . $row, $customer->invoices_count);
                $customersSheet->setCellValue('I' . $row, $customer->is_active ? 'Active' : 'Inactive');
                
                // Zebra striping
                if ($row % 2 == 0) {
                    $customersSheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]
                    ]);
                }
                
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $customersSheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders to all data
            $customersSheet->getStyle('A1:I' . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            
            // ===== VEHICLES SHEET =====
            $vehiclesSheet = $spreadsheet->createSheet();
            $vehiclesSheet->setTitle('Vehicles');
            
            // Set header row
            $vehiclesSheet->setCellValue('A1', 'No');
            $vehiclesSheet->setCellValue('B1', 'Plate Number');
            $vehiclesSheet->setCellValue('C1', 'Brand');
            $vehiclesSheet->setCellValue('D1', 'Model');
            $vehiclesSheet->setCellValue('E1', 'Year');
            $vehiclesSheet->setCellValue('F1', 'Color');
            $vehiclesSheet->setCellValue('G1', 'Chasis No');
            $vehiclesSheet->setCellValue('H1', 'Customer');
            $vehiclesSheet->setCellValue('I1', 'Customer Phone');
            $vehiclesSheet->setCellValue('J1', 'Status');
            
            // Style header row
            $vehiclesSheet->getStyle('A1:J1')->applyFromArray($headerStyle);
            
            // Fetch vehicles with customer
            $vehicles = Vehicle::with('customer')->orderBy('plate_number')->get();
            
            // Populate data rows
            $row = 2;
            foreach ($vehicles as $index => $vehicle) {
                $vehiclesSheet->setCellValue('A' . $row, $index + 1);
                $vehiclesSheet->setCellValue('B' . $row, $vehicle->plate_number);
                $vehiclesSheet->setCellValue('C' . $row, $vehicle->brand ?? '-');
                $vehiclesSheet->setCellValue('D' . $row, $vehicle->model ?? '-');
                $vehiclesSheet->setCellValue('E' . $row, $vehicle->year ?? '-');
                $vehiclesSheet->setCellValue('F' . $row, $vehicle->color ?? '-');
                $vehiclesSheet->setCellValue('G' . $row, $vehicle->chasis_no ?? '-');
                $vehiclesSheet->setCellValue('H' . $row, $vehicle->customer ? $vehicle->customer->name : '-');
                $vehiclesSheet->setCellValue('I' . $row, $vehicle->customer ? ($vehicle->customer->phone ?? '-') : '-');
                $vehiclesSheet->setCellValue('J' . $row, $vehicle->is_active ? 'Active' : 'Inactive');
                
                // Zebra striping
                if ($row % 2 == 0) {
                    $vehiclesSheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]
                    ]);
                }
                
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'J') as $col) {
                $vehiclesSheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders to all data
            $vehiclesSheet->getStyle('A1:J' . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            
            // Set active sheet to Customers
            $spreadsheet->setActiveSheetIndex(0);
            
            // Generate filename with timestamp
            $filename = 'Customers_Vehicles_Export_' . date('Y-m-d_His') . '.xlsx';
            
            // Create writer and save to output
            $writer = new Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
