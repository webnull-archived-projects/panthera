<table class="gridTable">
    <thead>
        <tr>
            <th></th>
            <th>{function="localize('Name', 'users')"}</th>
            <th>{function="localize('Primary group', 'users')"}</th>
            <th>{function="localize('Joined', 'users')"}</th>
            <th>{function="localize('Default language', 'users')"}</th>
            <th>{function="localize('Last login', 'users')"}</th>
            <th><span style="float: right;"><a onclick="navigateTo('?display=users&cat=admin&action=new_user');" style="cursor: pointer;"><img src="{$PANTHERA_URL}/images/admin/list-add.png" style="height: 15px;"></a></span></th>
        </tr>
    </thead>
        <tfoot>
            <tr>
            <td colspan="7"><em>{$uiPagerName="users"}
            {include="ui.pager"}
            </em></td>
            </tr>
        </tfoot>

        <tbody>
        {loop="$users_list"}
            <tr id="user_{$value.login}"}>
                <td style="width: 32px;"><img src="{$value.avatar}" style="max-height: 30px; max-width: 23px;"></td>
                <td {if="$value.banned"}style="text-decoration: line-through;"{/if}>{if="$view_users == True"}<a href='?display=users&cat=admin&action=account&uid={$value.id}' class='ajax_link'>{$value.name}</a>{else}{$value.name}{/if}</td>
                <td><a href="?display=acl&cat=admin&action=listGroup&group={$value.primary_group}" class="ajax_link">{$value.primary_group}</a></td>
                <td>{$value.joined}</td>
                <td>{$value.language|ucfirst}</td>
                <td>{$value.lastlogin}<br><small>{$value.lastip}</small></td>
                <td>
                    <a href="#" onclick="navigateTo('?display=users&cat=admin&action=editUser&uid={$value.id}')">
                        <img src="{$PANTHERA_URL}/images/admin/ui/edit.png" style="max-height: 22px;" alt="{function="localize('Edit', 'users')"}">
                    </a>
                    <a href="#" onclick="removeUser('{$value.login}');">
                        <img src="{$PANTHERA_URL}/images/admin/ui/delete.png" style="max-height: 22px;" alt="{function="localize('Remove')"}">
                    </a>
                </td>
            </tr>
        {/loop}
        </tbody>
</table>
