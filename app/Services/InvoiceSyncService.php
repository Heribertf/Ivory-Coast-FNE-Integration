<?php
namespace App\Services;

use App\Models\InvNum;
use App\Models\FneInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceSyncService
{
    public function syncAllInvoices($chunkSize = 500, $limit = 50)
    {
        Log::info('Starting full invoice sync');

        // InvNum::chunk($chunkSize, function ($invoices) {
        //     $this->processInvoiceBatch($invoices);
        // });


        $query = InvNum::orderByDesc('AutoIndex');

        if ($limit) {
            $query->limit($limit);
        }

        $query->chunk($chunkSize, function ($invoices) {
            $this->processInvoiceBatch($invoices);
        });
        Log::info('Completed full invoice sync');
    }

    public function syncRecentInvoices($hours = 24, $chunkSize = 500)
    {
        Log::info("Syncing invoices modified in last {$hours} hours");

        $cutoff = now()->subHours($hours);

        InvNum::where('InvNum_dModifiedDate', '>=', $cutoff)
            ->orWhere('InvNum_dCreatedDate', '>=', $cutoff)
            ->chunk($chunkSize, function ($invoices) {
                $this->processInvoiceBatch($invoices);
            });

        Log::info('Completed recent invoice sync');
    }

    protected function processInvoiceBatch($invoices)
    {
        DB::transaction(function () use ($invoices) {
            foreach ($invoices as $invoice) {
                $this->syncSingleInvoice($invoice);
            }
        });
    }

    public function syncSingleInvoice(InvNum $invoice)
    {
        try {
            FneInvoice::updateOrCreate(
                ['AutoIndex' => $invoice->AutoIndex],
                $this->prepareInvoiceData($invoice)
            );

            Log::debug("Synced invoice {$invoice->AutoIndex}: {$invoice->InvNumber}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync invoice {$invoice->AutoIndex}: " . $e->getMessage());
            return false;
        }
    }

    protected function prepareInvoiceData(InvNum $invoice)
    {
        return [
            'DocType' => $invoice->DocType,
            'DocVersion' => $invoice->DocVersion,
            'DocState' => $invoice->DocState,
            'DocFlag' => $invoice->DocFlag,
            'OrigDocID' => $invoice->OrigDocID,
            'InvNumber' => $invoice->InvNumber,
            'GrvNumber' => $invoice->GrvNumber,
            'GrvID' => $invoice->GrvID,
            'AccountID' => $invoice->AccountID,
            'Description' => $invoice->Description,
            'InvDate' => $invoice->InvDate,
            'OrderDate' => $invoice->OrderDate,
            'DueDate' => $invoice->DueDate,
            'DeliveryDate' => $invoice->DeliveryDate,
            'TaxInclusive' => $invoice->TaxInclusive,
            'Email_Sent' => $invoice->Email_Sent,
            'Address1' => $invoice->Address1,
            'Address2' => $invoice->Address2,
            'Address3' => $invoice->Address3,
            'Address4' => $invoice->Address4,
            'Address5' => $invoice->Address5,
            'Address6' => $invoice->Address6,
            'PAddress1' => $invoice->PAddress1,
            'PAddress2' => $invoice->PAddress2,
            'PAddress3' => $invoice->PAddress3,
            'PAddress4' => $invoice->PAddress4,
            'PAddress5' => $invoice->PAddress5,
            'PAddress6' => $invoice->PAddress6,
            'DelMethodID' => $invoice->DelMethodID,
            'DocRepID' => $invoice->DocRepID,
            'OrderNum' => $invoice->OrderNum,
            'DeliveryNote' => $invoice->DeliveryNote,
            'InvDisc' => $invoice->InvDisc,
            'InvDiscReasonID' => $invoice->InvDiscReasonID,
            'Message1' => $invoice->Message1,
            'Message2' => $invoice->Message2,
            'Message3' => $invoice->Message3,
            'ProjectID' => $invoice->ProjectID,
            'TillID' => $invoice->TillID,
            'POSAmntTendered' => $invoice->POSAmntTendered,
            'POSChange' => $invoice->POSChange,
            'GrvSplitFixedCost' => $invoice->GrvSplitFixedCost,
            'GrvSplitFixedAmnt' => $invoice->GrvSplitFixedAmnt,
            'OrderStatusID' => $invoice->OrderStatusID,
            'OrderPriorityID' => $invoice->OrderPriorityID,
            'ExtOrderNum' => $invoice->ExtOrderNum,
            'ForeignCurrencyID' => $invoice->ForeignCurrencyID,
            'InvDiscAmnt' => $invoice->InvDiscAmnt,
            'InvDiscAmntEx' => $invoice->InvDiscAmntEx,
            'InvTotExclDEx' => $invoice->InvTotExclDEx,
            'InvTotTaxDEx' => $invoice->InvTotTaxDEx,
            'InvTotInclDEx' => $invoice->InvTotInclDEx,
            'InvTotExcl' => $invoice->InvTotExcl,
            'InvTotTax' => $invoice->InvTotTax,
            'InvTotIncl' => $invoice->InvTotIncl,
            'OrdDiscAmnt' => $invoice->OrdDiscAmnt,
            'OrdDiscAmntEx' => $invoice->OrdDiscAmntEx,
            'OrdTotExclDEx' => $invoice->OrdTotExclDEx,
            'OrdTotTaxDEx' => $invoice->OrdTotTaxDEx,
            'OrdTotInclDEx' => $invoice->OrdTotInclDEx,
            'OrdTotExcl' => $invoice->OrdTotExcl,
            'OrdTotTax' => $invoice->OrdTotTax,
            'OrdTotIncl' => $invoice->OrdTotIncl,
            'bUseFixedPrices' => $invoice->bUseFixedPrices,
            'iDocPrinted' => $invoice->iDocPrinted,
            'iINVNUMAgentID' => $invoice->iINVNUMAgentID,
            'fExchangeRate' => $invoice->fExchangeRate,
            'fGrvSplitFixedAmntForeign' => $invoice->fGrvSplitFixedAmntForeign,
            'fInvDiscAmntForeign' => $invoice->fInvDiscAmntForeign,
            'fInvDiscAmntExForeign' => $invoice->fInvDiscAmntExForeign,
            'fInvTotExclDExForeign' => $invoice->fInvTotExclDExForeign,
            'fInvTotTaxDExForeign' => $invoice->fInvTotTaxDExForeign,
            'fInvTotInclDExForeign' => $invoice->fInvTotInclDExForeign,
            'fInvTotExclForeign' => $invoice->fInvTotExclForeign,
            'fInvTotTaxForeign' => $invoice->fInvTotTaxForeign,
            'fInvTotInclForeign' => $invoice->fInvTotInclForeign,
            'fOrdDiscAmntForeign' => $invoice->fOrdDiscAmntForeign,
            'fOrdDiscAmntExForeign' => $invoice->fOrdDiscAmntExForeign,
            'fOrdTotExclDExForeign' => $invoice->fOrdTotExclDExForeign,
            'fOrdTotTaxDExForeign' => $invoice->fOrdTotTaxDExForeign,
            'fOrdTotInclDExForeign' => $invoice->fOrdTotInclDExForeign,
            'fOrdTotExclForeign' => $invoice->fOrdTotExclForeign,
            'fOrdTotTaxForeign' => $invoice->fOrdTotTaxForeign,
            'fOrdTotInclForeign' => $invoice->fOrdTotInclForeign,
            'cTaxNumber' => $invoice->cTaxNumber,
            'cAccountName' => $invoice->cAccountName,
            'iProspectID' => $invoice->iProspectID,
            'iOpportunityID' => $invoice->iOpportunityID,
            'InvTotRounding' => $invoice->InvTotRounding,
            'OrdTotRounding' => $invoice->OrdTotRounding,
            'fInvTotForeignRounding' => $invoice->fInvTotForeignRounding,
            'fOrdTotForeignRounding' => $invoice->fOrdTotForeignRounding,
            'bInvRounding' => $invoice->bInvRounding,
            'iInvSettlementTermsID' => $invoice->iInvSettlementTermsID,
            'cSettlementTermInvMsg' => $invoice->cSettlementTermInvMsg,
            'iOrderCancelReasonID' => $invoice->iOrderCancelReasonID,
            'iLinkedDocID' => $invoice->iLinkedDocID,
            'bLinkedTemplate' => $invoice->bLinkedTemplate,
            'InvTotInclExRounding' => $invoice->InvTotInclExRounding,
            'OrdTotInclExRounding' => $invoice->OrdTotInclExRounding,
            'fInvTotInclForeignExRounding' => $invoice->fInvTotInclForeignExRounding,
            'fOrdTotInclForeignExRounding' => $invoice->fOrdTotInclForeignExRounding,
            'iEUNoTCID' => $invoice->iEUNoTCID,
            'iPOAuthStatus' => $invoice->iPOAuthStatus,
            'iPOIncidentID' => $invoice->iPOIncidentID,
            'iSupervisorID' => $invoice->iSupervisorID,
            'iMergedDocID' => $invoice->iMergedDocID,
            'iDocEmailed' => $invoice->iDocEmailed,
            'fDepositAmountForeign' => $invoice->fDepositAmountForeign,
            'fRefundAmount' => $invoice->fRefundAmount,
            'bTaxPerLine' => $invoice->bTaxPerLine,
            'fDepositAmountTotal' => $invoice->fDepositAmountTotal,
            'fDepositAmountUnallocated' => $invoice->fDepositAmountUnallocated,
            'fDepositAmountNew' => $invoice->fDepositAmountNew,
            'fDepositAmountTotalForeign' => $invoice->fDepositAmountTotalForeign,
            'fDepositAmountUnallocatedForeign' => $invoice->fDepositAmountUnallocatedForeign,
            'fRefundAmountForeign' => $invoice->fRefundAmountForeign,
            'KeepAsideCollectionDate' => $invoice->KeepAsideCollectionDate,
            'KeepAsideExpiryDate' => $invoice->KeepAsideExpiryDate,
            'cContact' => $invoice->cContact,
            'cTelephone' => $invoice->cTelephone,
            'cFax' => $invoice->cFax,
            'cEmail' => $invoice->cEmail,
            'cCellular' => $invoice->cCellular,
            // 'imgOrderSignature' => $invoice->imgOrderSignature,
            'iInsuranceState' => $invoice->iInsuranceState,
            'cAuthorisedBy' => $invoice->cAuthorisedBy,
            'cClaimNumber' => $invoice->cClaimNumber,
            'cPolicyNumber' => $invoice->cPolicyNumber,
            'dIncidentDate' => $invoice->dIncidentDate,
            'cExcessAccName' => $invoice->cExcessAccName,
            'cExcessAccCont1' => $invoice->cExcessAccCont1,
            'cExcessAccCont2' => $invoice->cExcessAccCont2,
            'fExcessAmt' => $invoice->fExcessAmt,
            'fExcessPct' => $invoice->fExcessPct,
            'fExcessExclusive' => $invoice->fExcessExclusive,
            'fExcessInclusive' => $invoice->fExcessInclusive,
            'fExcessTax' => $invoice->fExcessTax,
            'fAddChargeExclusive' => $invoice->fAddChargeExclusive,
            'fAddChargeTax' => $invoice->fAddChargeTax,
            'fAddChargeInclusive' => $invoice->fAddChargeInclusive,
            'fAddChargeExclusiveForeign' => $invoice->fAddChargeExclusiveForeign,
            'fAddChargeTaxForeign' => $invoice->fAddChargeTaxForeign,
            'fAddChargeInclusiveForeign' => $invoice->fAddChargeInclusiveForeign,
            'fOrdAddChargeExclusive' => $invoice->fOrdAddChargeExclusive,
            'fOrdAddChargeTax' => $invoice->fOrdAddChargeTax,
            'fOrdAddChargeInclusive' => $invoice->fOrdAddChargeInclusive,
            'fOrdAddChargeExclusiveForeign' => $invoice->fOrdAddChargeExclusiveForeign,
            'fOrdAddChargeTaxForeign' => $invoice->fOrdAddChargeTaxForeign,
            'fOrdAddChargeInclusiveForeign' => $invoice->fOrdAddChargeInclusiveForeign,
            'iInvoiceSplitDocID' => $invoice->iInvoiceSplitDocID,
            'cGIVNumber' => $invoice->cGIVNumber,
            'bIsDCOrder' => $invoice->bIsDCOrder,
            'iDCBranchID' => $invoice->iDCBranchID,
            'iSalesBranchID' => $invoice->iSalesBranchID,
            'InvNum_iBranchID' => $invoice->InvNum_iBranchID,
            'InvNum_dCreatedDate' => $invoice->InvNum_dCreatedDate,
            'InvNum_dModifiedDate' => $invoice->InvNum_dModifiedDate,
            'InvNum_iCreatedBranchID' => $invoice->InvNum_iCreatedBranchID,
            'InvNum_iModifiedBranchID' => $invoice->InvNum_iModifiedBranchID,
            'InvNum_iCreatedAgentID' => $invoice->InvNum_iCreatedAgentID,
            'InvNum_iModifiedAgentID' => $invoice->InvNum_iModifiedAgentID,
            'InvNum_iChangeSetID' => $invoice->InvNum_iChangeSetID,
            'InvNum_Checksum' => $invoice->InvNum_Checksum,
            'bIDFProccessed' => $invoice->bIDFProccessed,
            'iImportDeclarationID' => $invoice->iImportDeclarationID,
            'bSBSI' => $invoice->bSBSI,
            'cPermitNumber' => $invoice->cPermitNumber,
            'iStateID' => $invoice->iStateID,
            'iCancellationReasonID' => $invoice->iCancellationReasonID,
            'cDPOrdServiceTaskNo' => $invoice->cDPOrdServiceTaskNo,
            'cDSOrdServiceTaskNo' => $invoice->cDSOrdServiceTaskNo,
            'cDCrnServiceTaskNo' => $invoice->cDCrnServiceTaskNo,
            'cDSMExtOrderNum' => $invoice->cDSMExtOrderNum,
            'cHash' => $invoice->cHash,
            'cRevenueIntegration' => $invoice->cRevenueIntegration,
            'cQuoteNum' => $invoice->cQuoteNum,
            'last_sync_at' => now(),
        ];
    }
}