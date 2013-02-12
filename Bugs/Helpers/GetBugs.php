<?php
include "../dbConnect.php";

$assignedto=$_GET['assignedto'];

$subproj_query=mysql_query("SELECT DISTINCT TaskSubProjects.Name,Tasks.fkSubProject FROM Tasks
LEFT JOIN TaskSubProjects ON Tasks.fkSubProject=TaskSubProjects.keyTaskSubProjects
WHERE PriorityFlagSet=1 AND AssignedTo='$assignedto' ORDER BY Tasks.fkSubProject");
while($subprojnum=mysql_fetch_array($subproj_query))
{
	$subproj=$subprojnum['fkSubProject'];
	$subproj_name=$subprojnum['Name'];
	
	$bugs_query=mysql_query("SELECT keyTasks,Priority,AssignedTo,ReportedBy,DATE(DateEntered) AS DateEntr,
	Description,Notes FROM Tasks 
	WHERE PriorityFlagSet=1 AND AssignedTo='$assignedto' AND fkSubProject='$subproj' AND (TaskStatus is NULL OR TaskStatus<>'Closed')");
	
	if(mysql_num_rows($bugs_query) > 0)
	{
		while($bug=mysql_fetch_array($bugs_query))
		{
			$taskid=$bug['keyTasks'];
			$priority=$bug['Priority'];
			$assignedto=$bug['AssignedTo'];
			$reportedby=$bug['ReportedBy'];
			$date=$bug['DateEntr'];
			$desc=$bug['Description'];
			$tasknotes=$bug['Notes'];
			$tasknotes=utf8_encode($tasknotes);
			
			$bugs_details_query=mysql_query("SELECT keyTaskEvents,DATE(DateUpdated) AS DateUpdated,EntryBy,Notes FROM TaskEvents WHERE fkTasks='$taskid'");
			
			if(mysql_num_rows($bugs_details_query)>0)
			{
				while($bugdetail=mysql_fetch_array($bugs_details_query))
				{
					$keyTaskEvent=$bugdetail['keyTaskEvents'];
					$dateUpdated=$bugdetail['DateUpdated'];
					$entryBy=$bugdetail['EntryBy'];
					$notes=$bugdetail['Notes'];
					$notes=utf8_encode($notes);
					
					$node[]= new LeafNode($keyTaskEvent,$entryBy,$dateUpdated,$notes,"col","bug-notes",true,false,"","");
				}
				json_encode($node);
				$mnode[] = new MiddleNode($taskid,$priority,$assignedto,$reportedby,$date,$desc,$tasknotes,"col","bug",false,false,"","",$node);
				unset($node);
			}
			else
			{
				$mnode[]=new MiddleNodeLeaf($taskid,$priority,$assignedto,$reportedby,$date,$desc,$tasknotes,"col","bug",true,false,"","");		
			}
		}
		json_encode($mnode);
		$root[]= new RootNode($subproj_name,"col","project",false,false,"","",$mnode);
	}
	unset($mnode);
}
//$n2 = new TreeNode("reportby",$reportedby,"dataset",true,false,"","");
//$n3 = new TreeNode("date",$date,"report",true,false,"","");

//$nodes=array($r1,$n1);
echo json_encode($root);
 
class LeafNode{
	public $keyTaskEvent= "";
	public $reportby = "";
    public $rdate = "";
    public $tasknote= "";
    public $uiProvider= "";
    public $iconCls = "";
    public $leaf = true;
    public $draggable = false;
    public $href = "#";
    public $hrefTarget = "";
    public $children = "";

    function  __construct($keyTaskEvent,$reportby,$rdate,$tasknote,$uiProvider,$iconCls,$leaf,$draggable,
            $href,$hrefTarget) {
    
        $this->subproj = $keyTaskEvent;
        $this->reportby = $reportby;
        $this->rdate= $rdate;
        $this->tasknote=$tasknote;
        $this->uiProvider= $uiProvider;
        $this->iconCls = $iconCls;
        $this->leaf = $leaf;
        $this->draggable = $draggable;
        $this->href = $href;
        $this->hrefTarget = $hrefTarget; 
     }    
}

class MiddleNode {
    public $taskid= "";
    public $priority= "";
	public $assignto = "";
    public $reportby = "";
    public $rdate = "";
    public $description= "";
    public $tasknote="";
    public $uiProvider= "";
    public $iconCls = "";
    public $leaf = true;
    public $draggable = false;
    public $href = "#";
    public $hrefTarget = "";
    public $children = "";

    function  __construct($taskid,$priority,$assignto,$reportby,$rdate,$description,$tasknote,$uiProvider,$iconCls,$leaf,$draggable,
            $href,$hrefTarget,$children) {
    
        //$this->taskid = $taskid;
        $this->subproj=$taskid;
        $this->priority= $priority;
        $this->assignto = $assignto;
        $this->reportby = $reportby;
        $this->rdate= $rdate;
        $this->description=$description;
        $this->tasknote=$tasknote;
        $this->uiProvider= $uiProvider;
        $this->iconCls = $iconCls;
        $this->leaf = $leaf;
        $this->draggable = $draggable;
        $this->href = $href;
        $this->hrefTarget = $hrefTarget; 
        $this->children=$children;   
    }    
}

class MiddleNodeLeaf {
    public $taskid= "";
    public $priority= "";
	public $assignto = "";
    public $reportby = "";
    public $rdate = "";
    public $description= "";
    public $tasknote="";
    public $uiProvider= "";
    public $iconCls = "";
    public $leaf = true;
    public $draggable = false;
    public $href = "#";
    public $hrefTarget = "";

    function  __construct($taskid,$priority,$assignto,$reportby,$rdate,$description,$tasknote,$uiProvider,$iconCls,$leaf,$draggable,
            $href,$hrefTarget) {
    
        //$this->taskid = $taskid;
        $this->subproj=$taskid;
        $this->priority= $priority;
        $this->assignto = $assignto;
        $this->reportby = $reportby;
        $this->rdate= $rdate;
        $this->description=$description;
        $this->tasknote=$tasknote;
        $this->uiProvider= $uiProvider;
        $this->iconCls = $iconCls;
        $this->leaf = $leaf;
        $this->draggable = $draggable;
        $this->href = $href;
        $this->hrefTarget = $hrefTarget;    
    }    
}

class RootNode {
	public $subproj= "";
    public $uiProvider= "";
    public $iconCls = "";
    public $leaf = false;
    public $draggable = false;
    public $href = "#";
    public $hrefTarget = "";
    public $children = "";

    function  __construct($subproj,$uiProvider,$iconCls,$leaf,$draggable,
            $href,$hrefTarget,$children) {
    
        $this->subproj=$subproj;
        $this->uiProvider= $uiProvider;
        $this->iconCls = $iconCls;
        $this->leaf = $leaf;
        $this->draggable = $draggable;
        $this->href = $href;
        $this->hrefTarget = $hrefTarget;
        $this->children= $children;    
    }    	

}

?>