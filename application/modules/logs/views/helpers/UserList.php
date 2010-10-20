<?php
class Logs_View_Helper_UserList extends Zend_View_Helper_Abstract
{
    /**
     * Display a clickable list of users
     *
     * This is a simple helper that should probably extend formSelect to display
     * a list of developers in the system. When one is clicked on, their logs are displayed
     *
     * @param array $userList
     * @param string $selected
     */
    public function userList($userList, $selected)
    {
        $userList = array_merge(
            array('_all_' => 'All Users'), $userList
        );

        $output = "<script>
        $(function() {
            $('#userList').change(function() {
                var userUrl = $(this).attr(\"value\");
                if (userUrl != '_all_') {
                    url = \"http://dailylogs/logs/user/\" + userUrl;
                    window.location.href = url;
                } else {
                    url = \"http://dailylogs/logs/user/\";
                    window.location.href = url;
                }
            });
        });
        </script>";

        $output .= $this->view->formSelect('userList', $selected, null, $userList);
        return $output;
    }
}