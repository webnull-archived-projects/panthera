<script type="text/javascript">
$(document).ready(function(){
// AJAX UPLOAD FORM
    $('#upload_form').submit(function () {
        panthera.jsonPOST({ data: '#upload_form', async: false, isUploading: true,
            before: function () {
                $('#upload_box_window').css({ opacity: 1 });
            
            }, progress: function (precent, start, end) {
                $('#upload_box_window').css({ opacity: (1-(precent/100)) });
            },
     
            success: function(response) {
                if (response.status == "success")
                {
                    var category = $("#category").val();
                    panthera.popup.create('?display=upload&cat=admin&directory='+category+'&popup=True');
                }
            } 
        });
        
        return false;
      });
});
</script>

<form action="?display=upload&cat=admin&action=popupHandleFile&popup=true" method="POST" enctype="multipart/form-data" id="upload_form">
    <table class="formTable" style="margin: 0 auto; margin-top: 30px; margin-bottom: 30px;" id="upload_box_window">
        <thead>
            <tr>
                <td colspan="2" class="formTableHeader" style="padding-top: 0px; padding-bottom: 30px;">
                    <p style="color: #e5ebef; padding: 0px; margin: 0px; margin-left: 30px;">{function="localize('Upload a new file', 'upload')"} - {if="$category->exists()"}{$category->getName()}{/if}</p>
                </td>
            </tr>
         </thead>
         
         <tbody>
             <tr style="color: white;">
                <th><a title="{function="slocalize('Allowed file types: %s', 'upload', $category->mime_type)"}">{function="localize('Select a file', 'upload')"}</a>:</th>
                <td><input type="file" name="input_file"> <input type="hidden" name="MAX_FILE_SIZE" value="{$max_file_size}" /></td>
             </tr>
             
             {if="$category->exists()"}
             <tr>
             	<th>{function="slocalize('Allowed file types: %s', 'upload', '')"}</th>
             	<td style="color: white;">{$category->mime_type}</td>
             </tr>
             
             <tr>
             	<th>{function="localize('Max file size', 'upload')"}:</th>
             	<td style="color: white;">{$category->getMaxFilesize(true)}</td>
             </tr>
             {/if}
             
             <tr>
             	<th>{function="localize('Secure access only for me', 'upload')"}:</th>
             	<td><input type="checkbox" name="protected" value="1" checked></td>
             <tr>
                 <th>{function="localize('Description', 'upload')"}:</th>
                 <td><input type="text" name="input_description" style="width: 95%;"></td>
             </tr>
             <tr>
                 <th>{function="localize('Category', 'upload')"}:</th>
                 <td>
                     <select name="directory" style="width: 95%;" id="category">
                         {loop="$categories"}
                            <option {if="$value->name == $setCategory"} selected {/if}>{$value->getName()}</option>
                         {/loop}
                     </select>
                 </td>
             </tr>
         </tbody>
         <tfoot>
             <tr>
                 <td colspan="5" style="padding-top: 25px;">
                     <input type="submit" value="{function="localize('Send')"}" style="float: right; margin-right: 30px;">
                     <input type="button" id="_upl_back_btn" value="{function="localize('Back')"}" style="float: left; margin-left: 30px;" onclick="panthera.popup.toggle('?display=upload&cat=admin&directory={$setCategory}&popup=True')">
                 </td>
             </tr>
         </tfoot>
    </table>
</form>
