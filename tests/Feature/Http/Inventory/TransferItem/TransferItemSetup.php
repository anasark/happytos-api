<?php 

namespace Tests\Feature\Http\Inventory\TransferItem;

use App\Model\Master\Item;
use App\Model\Master\User as TenantUser;
use App\Model\Master\Warehouse;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\Template\ChartOfAccountImport;
use App\Model\Form;
use App\Model\Inventory\TransferItem\TransferItem;
use App\Helpers\Inventory\InventoryHelper;
use App\Model\Accounting\ChartOfAccount;
use App\Model\Master\ItemUnit;
use Carbon\Carbon;

trait TransferItemSetup {
  private $tenantUser;
  private $branchDefault;
  private $warehouse;
  private $unit;
  private $item;
  private $customer;
  private $approver;
  private $coa;

  public function setUp(): void
  {
    parent::setUp();

    $this->signIn();
    $this->setProject();
    $this->importChartOfAccount();

    $this->tenantUser = TenantUser::find($this->user->id);
    $this->branchDefault = $this->tenantUser->branches()
        ->where('is_default', true)
        ->first();

    $this->setUpTransferItemPermission();
    $this->setUserWarehouse($this->branchDefault);
    $_SERVER['HTTP_REFERER'] = 'http://www.example.com/';
  }

  protected function setUpTransferItemPermission()
  {
    \App\Model\Auth\Permission::createIfNotExists('create transfer item');
    \App\Model\Auth\Permission::createIfNotExists('update transfer item');
    \App\Model\Auth\Permission::createIfNotExists('delete transfer item');
    \App\Model\Auth\Permission::createIfNotExists('read transfer item');
    \App\Model\Auth\Permission::createIfNotExists('approve transfer item');
  }

  protected function setCreatePermission()
  {
    $permission = \App\Model\Auth\Permission::where('name', 'create transfer item')->first();
    $hasPermission = new \App\Model\Auth\ModelHasPermission();
    $hasPermission->permission_id = $permission->id;
    $hasPermission->model_type = 'App\Model\Master\User';
    $hasPermission->model_id = $this->user->id;
    $hasPermission->save();
  }

  protected function unsetCreatePermission()
  {
    $permission = \App\Model\Auth\Permission::where('name', 'create transfer item')->first();
    $hasPermission = \App\Model\Auth\ModelHasPermission::where('permission_id', $permission->id);
    $hasPermission->delete();
  }

  protected function setUpdatePermission()
  {
    $permission = \App\Model\Auth\Permission::where('name', 'update transfer item')->first();
    $hasPermission = new \App\Model\Auth\ModelHasPermission();
    $hasPermission->permission_id = $permission->id;
    $hasPermission->model_type = 'App\Model\Master\User';
    $hasPermission->model_id = $this->user->id;
    $hasPermission->save();
  }

  protected function setDeletePermission()
  {
    $permission = \App\Model\Auth\Permission::where('name', 'delete transfer item')->first();
    $hasPermission = new \App\Model\Auth\ModelHasPermission();
    $hasPermission->permission_id = $permission->id;
    $hasPermission->model_type = 'App\Model\Master\User';
    $hasPermission->model_id = $this->user->id;
    $hasPermission->save();
  }

  protected function setReadPermission()
  {
    $permission = \App\Model\Auth\Permission::where('name', 'read transfer item')->first();
    $hasPermission = new \App\Model\Auth\ModelHasPermission();
    $hasPermission->permission_id = $permission->id;
    $hasPermission->model_type = 'App\Model\Master\User';
    $hasPermission->model_id = $this->user->id;
    $hasPermission->save();
  }

  protected function setApprovePermission()
  {
    $permission = \App\Model\Auth\Permission::where('name', 'approve transfer item')->first();
    $hasPermission = new \App\Model\Auth\ModelHasPermission();
    $hasPermission->permission_id = $permission->id;
    $hasPermission->model_type = 'App\Model\Master\User';
    $hasPermission->model_id = $this->user->id;
    $hasPermission->save();
  }

  private function setUserWarehouse($branch = null)
  {
      $warehouse = $this->createWarehouse($branch);
      $this->tenantUser->warehouses()->syncWithoutDetaching($warehouse->id);
      foreach ($this->tenantUser->warehouses as $warehouse) {
          $warehouse->pivot->is_default = true;
          $warehouse->pivot->save();
  
          $this->warehouse = $warehouse;
      }
  }

  private function createWarehouse($branch = null)
  {
      $warehouse = new Warehouse();
      $warehouse->name = $this->faker->name;
      if ($branch) {
          $warehouse->branch_id = $branch->id;
      }
      $warehouse->save();

      return $warehouse;
  }

  protected function getStock($item, $warehouse, $options)
  {
      return InventoryHelper::getCurrentStock(
          $item,
          convert_to_server_timezone(now(), null, 'Asia/Jakarta'),
          $warehouse,
          $options
      );
  }

  private function importChartOfAccount()
  {
      Excel::import(new ChartOfAccountImport(), storage_path('template/chart_of_accounts_manufacture.xlsx'));


      $this->artisan('db:seed', [
          '--database' => 'tenant',
          '--class' => 'SettingJournalSeeder',
          '--force' => true,
      ]);
  }

  protected function unsetDefaultBranch()
  {
      $this->branchDefault->pivot->is_default = false;
      $this->branchDefault->save();

      $this->tenantUser->branches()->detach($this->branchDefault->pivot->branch_id);
  }

  protected function unsetDefaultWarehouse()
  {
      $this->warehouse->pivot->is_default = false;
      $this->warehouse->pivot->save();
  }

  protected function getDate($date = null)
  {
      $tz = 'Asia/Jakarta';

      Carbon::setLocale('id');

      $time = is_null($date) ? Carbon::now($tz) : Carbon::parse($date, $tz);

      return $time->format('d F Y H:i');
  }

  public function dummyData($item = null)
  {
      if (!$item) {
        $coa = ChartOfAccount::orderBy('id', 'desc')->first();
          
        $item = new Item;
        $item->name = $this->faker->name;
        $item->chart_of_account_id = $coa->id;
        $item->save();
      }

      $unit = factory(ItemUnit::class)->make();
      $item->units()->save($unit);
      
      $to_warehouse = factory(Warehouse::class)->create();

      $distribution_warehouse = new Warehouse();
      $distribution_warehouse->name = 'DISTRIBUTION WAREHOUSE';
      $distribution_warehouse->save();

      $form = new Form;
      $form->date = now()->toDateTimeString();
      $form->created_by = $this->user->id;
      $form->updated_by = $this->user->id;
      $form->save();

      $options = [];
      if ($item->require_expiry_date) {
          $options['expiry_date'] = $item->expiry_date;
      }
      if ($item->require_production_number) {
          $options['production_number'] = $item->production_number;
      }

      $options['quantity_reference'] = $item->quantity;
      $options['unit_reference'] = $item->unit;
      $options['converter_reference'] = $item->converter;

      InventoryHelper::increase($form, $this->warehouse, $item, 100, "PCS", 1, $options);
      
      $data = [
          "date" => now()->timezone('Asia/Jakarta')->toDateTimeString(),
          "increment_group" => date("Ym"),
          "notes" => "Some notes",
          "warehouse_id" => $this->warehouse->id,
          "to_warehouse_id" => $to_warehouse->id,
          "driver" => "Some one",
          "request_approval_to" => $this->user->id,
          "items" => [
              [
                  "item_id" => $item->id,
                  "item_name" => $item->name,
                  "unit" => $unit->label,
                  "converter" => 1,
                  "quantity" => 10,
                  "stock" => 100,
                  "balance" => 90,
                  "warehouse_id" => $this->warehouse->id,
                  'dna' => [
                      [
                          "quantity" => 10,
                          "item_id" => $item->id,
                          "expiry_date" => date('Y-m-d', strtotime('1 year')),
                          "production_number" => "sample",
                          "remaining" => 100,
                      ]
                  ]
              ]
          ]
      ];

      return $data;
  }

  public function createTransferItem()
  {
      $this->setCreatePermission();

      $coa = ChartOfAccount::orderBy('id', 'desc')->first();
        
      $item = new Item;
      $item->name = $this->faker->name;
      $item->chart_of_account_id = $coa->id;
      $item->save();

      $data = $this->dummyData($item);

      $response = $this->json('POST', '/api/v1/inventory/transfer-items', $data, $this->headers);

      $transferItem = TransferItem::where('id', $response->json('data')["id"])->first();
      
      return $transferItem;
  }
}