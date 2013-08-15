{$site_header}
{function="localizeDomain('cpages')"}
<script type="text/javascript">
$('.ajax_link').click(function (event) { event.preventDefault(); navigateTo(jQuery(this).attr('href')); return false;});

/**
  * Submit add_page form
  *
  * @author Mateusz Warzyński
  */

$('#add_page').submit(function () {
    panthera.jsonPOST({ data: '#add_page', messageBox: 'userinfoBox', mce: 'tinymce_all', success: function (response) {
            if (response.status == "success")
                navigateTo("?display=custom&cat=admin");
        }
    });

    return false;

});


/**
  * Remove custom page from database
  *
  * @author Mateusz Warzyński
  */

function removeCustomPage(id)
{
    panthera.jsonPOST({ url: '{$AJAX_URL}?display=custom&cat=admin&action=delete_page&pid='+id, data: '', success: function (response) {
            if (response.status == "success")
                jQuery('#custompage_row_'+id).remove();
        }
    });
}


/**
  * Get custom pages by language
  *
  * @author Mateusz Warzyński
  */

function getOtherCustomPages()
{
    value = jQuery('#language').val();
    navigateTo("?display=custom&cat=admin&lang="+value);
}
</script>

    <div class="titlebar">{function="localize('Static pages', 'custompages')"}
        {include="_navigation_panel"}
    </div>

    <div class="grid-1">
        <table class="gridTable" style="padding: 0px; margin: 0px;">
            <thead>
                <tr>
                    <th scope="col" class="rounded-company">{function="localize('Title', 'custompages')"}</th>
                    <th>{function="localize('Created', 'custompages')"}</th>
                    <th>{function="localize('Modified', 'custompages')"}</th>
                    <th>{function="localize('Avaliable in', 'custompages')"}</th>
                    <th>{function="localize('Options', 'custompages')"}</th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <td colspan="7" class="rounded-foot-left"><em>
                    Panthera - {function="localize('Custom pages', 'custompages')"}</em></td>
                </tr>
            </tfoot>

            <tbody>
              {loop="$pages_list"}
                <tr id="custompage_row_{$value.id}">
                    <td><a href="{$AJAX_URL}?display=custom&cat=admin&action=edit_page&uid={$value.unique}" class="ajax_link">{$value.title|localize}</a></td>
                    <td>{$value.created} {function="localize('by', 'custompages')"} {$value.author_name}</td>
                    <td>{if="$value['created'] == $value['modified']"}{function="localize('without changes', 'custompages')"}{else}{$value.modified} {function="localize('by', 'custompages')"} {$value.mod_author_name}{/if}</td>
                    <td>
                        <select>
                            {loop="$value['languages']"}
                            <option>{$key}</option>
                            {/loop}
                        </select>
                    </td>
                    <td><input type="button" value="{function="localize('Delete', 'messages')"}" onclick="removeCustomPage({$value.id});"></td>
                </tr>
              {/loop}
            </tbody>
        </table>

        <br>

        <table class="gridTable" style="padding: 0px; margin: 0px; width: 50%;">
            <thead>
                <tr>
                    <th colspan="4">{function="localize('Options', 'custompages')"}</th>
                </tr>
            </thead>

            <form action="{$AJAX_URL}?display=custom&cat=admin&action=add_page" method="POST" id="add_page">
            <tbody>
                <tr id="tr_newCustomPage">
                    <td style="width: 35%;">{function="localize('Add new custom page', 'custompages')"}: </td>
                    <td style="width: 40%;"><input name="title" type="text" placeholder='{function="localize('Title of new custom page', 'custompages')"}' style="margin-right: 15px; width: 90%;"></td>
                    <td style="width: 80px;">
                        <select name="language" style="margin-right: 16px;">
                        {loop="$locales"}
                            <option value="{$key}">{$key}</option>
                        {/loop}
                            <option value="all">{function="localize('all', 'custompages')"}</option>
                        </select>
                    
                    <input type="submit" value="&nbsp;{function="localize('Add')"}&nbsp;"></td>
                </tr>
                
                <tr>
                    <td>{function="localize('Filter by language', 'custompages')"}:</td>
                    <td colspan="2"><select onChange="getOtherCustomPages()" id="language">
                         {loop="$locales"}
                           <option value="{$key}" {if="$current_lang == $key"} selected {/if}>{$key}</option>
                         {/loop}
                           <option value="" {if="$current_lang == ''"} selected {/if} >{function="localize('all', 'custompages')"}</option>
                        </select>
                   </td>
                </tr>
            </tbody>
            </form>
        </table>
    </div>
</div>

