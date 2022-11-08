<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\Inventory\InventoryDetailCollection;
use App\Model\Form;
use App\Model\Inventory\Inventory;
use Illuminate\Http\Request;

class InventoryDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $itemId
     * @return InventoryDetailCollection
     */
    public function index(Request $request, $itemId)
    {
        $dateFrom = convert_to_server_timezone($request->get('date_from'));
        $dateTo = convert_to_server_timezone($request->get('date_to'));
        
        $inventories = Inventory::from(Inventory::getTableName() . ' as ' . Inventory::$alias)->eloquentFilter($request)
            ->join(Form::getTableName() . ' as ' . Form::$alias, Form::$alias . '.id', '=', Inventory::$alias . '.form_id')
            ->where('inventory.item_id', $itemId)
            ->whereBetween('form.date', [$dateFrom, $dateTo]);

        if ($request->has('warehouse_id')) {
            $inventories = $inventories->where('inventory.warehouse_id', $request->get('warehouse_id'));
        }

        $inventories = $inventories->orderBy('form.date', 'asc');

        $inventories = pagination($inventories, $request->get('limit'));

        $inventoryCollection = new ApiCollection($inventories);

        return $inventoryCollection;
    }
}
