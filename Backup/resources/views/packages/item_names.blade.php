@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">All Item Names in Database</h1>
            <div>
                <a href="{{ route('packages.bom_import') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to BOM Import
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list"></i> Total Items: <strong>{{ $items->count() }}</strong></span>
                    <input type="text" id="searchBox" class="form-control w-50" placeholder="Search item names...">
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Use this list to verify exact item names for
                    BOM import. Copy the exact name from the "Item Name" column to your Excel file.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 50%">Item Name</th>
                                <th style="width: 25%">Item Code</th>
                                <th style="width: 20%">Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $item->name }}</strong></td>
                                    <td><code>{{ $item->code }}</code></td>
                                    <td>{{ $item->smallestUom->code ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchBox = document.getElementById('searchBox');
            const table = document.getElementById('itemsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            searchBox.addEventListener('keyup', function() {
                const filter = searchBox.value.toLowerCase();

                for (let i = 0; i < rows.length; i++) {
                    const itemName = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                    const itemCode = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();

                    if (itemName.includes(filter) || itemCode.includes(filter)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            });
        });
    </script>
@endsection
