<?php

namespace App\Http\Controllers\Api\Inventory\TransferItem;

use App\Helpers\Inventory\InventoryHelper;
use App\Helpers\Journal\JournalHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\TransferItem\ApproveTransferItemRequest;
use App\Http\Resources\ApiResource;
use App\Model\Inventory\TransferItem\TransferItem;
use Illuminate\Http\Request;

class TransferItemCancellationApprovalController extends Controller
{
    /**
     * @param ApproveTransferItemRequest $request
     * @param Request $request
     * @param $id
     * @return ApiResource
     */
    public function approve(ApproveTransferItemRequest $request, $id)
    {
        $transferItem = TransferItem::findOrFail($id);
        $transferItem->form->cancellation_approval_by = auth()->user()->id;
        $transferItem->form->cancellation_approval_at = now();
        $transferItem->form->cancellation_status = 1;
        $transferItem->form->save();

        JournalHelper::delete($transferItem->form->id);
        InventoryHelper::delete($transferItem->form->id);

        return new ApiResource($transferItem);
    }

    /**
     * @param ApproveTransferItemRequest $request
     * @param Request $request
     * @param $id
     * @return ApiResource
     */
    public function reject(ApproveTransferItemRequest $request, $id)
    {
        $request->validate([ 'reason' => 'required' ]);
        
        $transferItem = transferItem::findOrFail($id);
        $transferItem->form->cancellation_approval_by = auth()->user()->id;
        $transferItem->form->cancellation_approval_at = now();
        $transferItem->form->cancellation_approval_reason = $request->get('reason');
        $transferItem->form->cancellation_status = -1;
        $transferItem->form->save();

        return new ApiResource($transferItem);
    }
}
