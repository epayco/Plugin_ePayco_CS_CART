{$is_update_for_all_available = !$selected_storefront_id
    && $app['storefront.repository']->getCount(['cache' => true]) > 1
}
<div id="epayco_logo_uploader" class="in collapse {if $is_update_for_all_available}disable-overlay-wrap{/if}">
    {if $is_update_for_all_available}
        <div class="disable-overlay" id="pp_logo_disable_overlay"></div>
    {/if}
    <div class="control-group">
        <label class="control-label" for="elm_epayco_logo">{__("epayco_logo")}:</label>
        <div class="controls">
            {include file="common/attach_images.tpl"
                image_name="epayco_logo"
                image_object_type="epayco_logo"
                image_pair=$pp_settings.main_pair
                no_thumbnail=true
                hide_alt=true
            }
            {if $is_update_for_all_available}
                <div class="right">
                    {include file="buttons/update_for_all.tpl"
                        display=true
                        object_id="pp_settings"
                        name="pp_settings[pp_logo_update_all_storefronts]"
                        hide_element="epayco_logo_uploader"
                        component="epayco.epayco_logo_uploader"
                    }
                </div>
            {/if}
            <p class="muted description">{__("ttc_epayco_logo")}</p>
        </div>
    </div>
</div>
<script>
    (function(_, $) {
        $('[data-ca-update-for-all="epayco.epayco_logo_uploader"]').on('click', function () {
            $('#epayco_logo_uploader').toggleClass('disable-overlay-wrap');
            $('#pp_logo_disable_overlay').toggleClass('disable-overlay');
        });
    })(Tygh, Tygh.$);
</script>
