{if fn_allowed_for('ULTIMATE') && !$runtime.company_id || $runtime.simple_ultimate || fn_allowed_for('MULTIVENDOR')}
    {$statuses = $smarty.const.STATUSES_ORDER|fn_get_simple_statuses}

    <div id="text_epayco_partial_refund_action" class="in collapse">
        <div class="control-group">
            <label class="control-label" for="elm_partial_refund">
                {__("order_status")}:
                <p class="muted description">{__("tt_addons_epayco_settings_partial_refund_action_order_status")}</p>
            </label>
            <div class="controls">
                <select name="pp_settings[partial_refund_action]" id="elm_partial_refund">
                    <option value="{$smarty.const.epayco_PARTIAL_REFUND_IGNORE}"{if $pp_settings.partial_refund_action == "{$smarty.const.epayco_PARTIAL_REFUND_IGNORE}"} selected="selected"{/if}>{__("addons.epayco.do_not_change")}</option>
                    <optgroup label="{__("addons.epayco.set_status_to")}">
                        {foreach from=$statuses item="s" key="k"}
                            <option value="{$k}"{if $pp_settings.partial_refund_action == $k} selected="selected"{/if}>{$s}</option>
                        {/foreach}
                    </optgroup>
                </select>
            </div>
        </div>
    </div>
{/if}