{if $is_refund == "Y" && $order_info.payment_method.processor_id|fn_is_epayco_processor}
    <div class="control-group notify-department">
        <label class="control-label" for="elm_epayco_perform_refund">
            {__("addons.epayco.rma.perform_refund")}
            <p class="muted description">{__("ttc_addons.epayco.rma.perform_refund")}</p>
        </label>
        <div class="controls">
            {if $return_info.return_id|fn_is_epayco_refund_performed}
                <p class="label label-success">{__("refunded")}</p>
            {else}
                <label class="checkbox">
                    <input type="checkbox" name="change_return_status[epayco_perform_refund]" id="elm_epayco_perform_refund" value="Y" />
                </label>
            {/if}
        </div>
    </div>
{/if}