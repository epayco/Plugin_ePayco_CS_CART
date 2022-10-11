<div class="control-group">
    <label class="control-label" for="p_cust_id_cliente">P_CUST_ID_CLIENTE:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][p_cust_id_cliente]" 
            id="p_cust_id_cliente"
            value="{$processor_params.p_cust_id_cliente}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="p_public_key">PUBLIC_KEY:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][p_public_key]" 
            id="p_public_key"
            value="{$processor_params.p_public_key}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="p_key">P_KEY:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][p_key]" id="p_key" 
        value="{$processor_params.p_key}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="p_test_request">TEST_REQUEST:</label>
    <div class="controls">
        <select name="payment_data[processor_params][p_test_request]" id="p_test_request">
            {if $processor_params.p_test_request == 'TRUE'}
                <option value="TRUE" selected="selected">SI</option>
                <option value="FALSE">NO</option>
            {else}
                <option value="TRUE">SI</option>
                <option value="FALSE" selected="selected">NO</option>
            {/if}
        </select>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="p_type_checkout">TYPE_CHECKOUT:</label>
    <div class="controls">
        <select name="payment_data[processor_params][p_type_checkout]" id="p_type_checkout">
            {if $processor_params.p_type_checkout == 'TRUE'}
                <option value="TRUE" selected="selected">STANDART</option>
                <option value="FALSE">ONE PAGE</option>
            {else}
                <option value="TRUE">STANDART</option>
                <option value="FALSE" selected="selected">ONE PAGE</option>
            {/if}
        </select>
    </div>
</div>