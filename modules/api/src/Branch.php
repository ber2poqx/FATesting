<?php

namespace FAAPI;

$path_to_root = "../..";


class Branch
{
    // Get Items
    public function get($rest)
    {
        $req = $rest->request();

        $page = $req->get("page");

        if ($page == null) {
            $this->branch_all(null);
        } else {
            // If page = 1 the value will be 0, if page = 2 the value will be 1, ...
            $from = --$page * RESULTS_PER_PAGE;
            $this->branch_all($from);
        }
    }

    private function branch_all($from = null)
    {
        global $db_connections;

        $info = array();

        for ($i = 0; $i < count($db_connections); $i++) {
            $info[] = array(
                'branch_id' => $i,
                'branch_name' => $db_connections[$i]["name"],
                'branch_code' => $db_connections[$i]["branch_code"],
                'branch_dbname' => $db_connections[$i]["dbname"],
            );
        }
        

        api_success_response(json_encode($info));
    }
}
