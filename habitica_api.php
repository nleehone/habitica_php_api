<?php

class Habitica{
    private $usr;
    private $key;
    private $api_base = 'https://habitica.com/api/v2/';

    public function __construct($usr, $key){
	/* Pass and store the user id and api-key in the Habitica instance
	   The user-id and api-key can be found under Settings->API
	   The suggested way to pass the information to Habitica is to have the user and key
	   set up as environment variables and call:
	   $hb = new Habitica(getenv('HABITICA_USER_ID'), getenv('HABITICA_API_KEY'));

	   @param $usr = user-id
	   @param $key = api-key
	 */
	$this->usr = $usr;
	$this->key = $key;
    }

    public function habiticaGet($api){
	/* Send a GET request to habitica and return the raw json results
	   
	   @param $api: The api that you want to query
	*/
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $this->api_base . $api);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	    'x-api-user: ' . $this->usr,
		'x-api-key: '. $this->key
	));
	$response = curl_exec($curl);
	curl_close($curl);

	return $response;
    }
    
    public function habiticaPost($api, $data=false){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $this->api_base . $api);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
		'x-api-user: ' . $this->usr,
		'x-api-key: '. $this->key
	));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	if($data != false){
	    $data = json_encode($data);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
    }

    public function getAllTasks(){
	return $this->habiticaGet('user/tasks');
    }

    public function getAllTasksWithName($name){
	$tasks = $this->getAllTasks();
	return $this->filterTasksByName($tasks, $name);
    }

    public function getTask($id){
	return $this->habiticaGet('user/tasks/' . $id);
    }

    private function filterTasksByName($tasks, $name){
	$found_tasks = array();
	foreach($tasks as $task){
	    if($task->text === $name)
		array_push($found_tasks, $task);
	}
	return $found_tasks;
    }

    private function filterTasksByCompleted($tasks, $completed){
	$found_tasks = array();
	foreach($tasks as $task){
	    if($task->completed === $completed)
		array_push($found_tasks, $task);
	}
	return $found_tasks;
    }

    private function filterTasksByType($tasks, $type){
	$found_tasks = array();
	foreach($tasks as $task){
	    if($task->type === $type)
		array_push($found_tasks, $task);
	}
	return $found_tasks;
    }

    public function createRecurringTask($name, $starts, $recurs, $type='todo'){
	/* Creates a recurring task that will recur on specific dates.
	   
	   This function has a known problem when using monthly recursion with dates above 28.
	   The problem is that adding 1 month to Jan 30 results in March 2st, which might
	   not be the expected behaviour. This propagages when the month is added multiple times
	   so a task that began on 2010-11-30 will become 2015-10-02 if the current date is 
	   2015-09-09.

	   Args:
	     $name: The text that will appear on the habitica dashboard
	     $when: The time at which the task will recur. 
	       This will automatically set a due date
	     $type: The type of task (defaults to 'todo'). Possible values are:
	       ['todo', 'habit']
	*/
	# First check if there is already a task with this name
	$tasks = json_decode($this->getAllTasks());
	$tasks = $this->filterTasksByName($tasks, $name);
	$tasks = $this->filterTasksByType($tasks, $type);
	
	if(!empty($this->filterTasksByCompleted($tasks, false))){
	    # If there is already a task then we don't add a new instance
	    return "Task already exists";
	}
	else if(!empty($this->filterTasksByCompleted($tasks, true))){
	    # If there was a previous task with this name then we use its
	    # completion date to set the next completion date

	    $newtask = new stdClass();
	    $newtask->text = $name;
	    $newtask->type = $type;

	    $date = date_create($starts);
	    $previous_task_dates = array();
	    foreach($tasks as $task){
		array_push($previous_task_dates, date_create($task->date)->getTimestamp());
	    }
	    $previous_task_time = max($previous_task_dates);

	    # Add the recurrance to the previous task date until the time is
	    # past the last completion
	    while($date->getTimestamp() <= $previous_task_time){
		date_modify($date, $recurs);
	    }
	    $newtask->date = date("Y-m-d", $date->getTimestamp());

	    return $this->habiticaPost('user/tasks', $newtask);
	}
	else{
	    # If there is no task at all with this name then we create a new task
	    $task = new stdClass();
	    $task->text = $name;
	    $task->type = $type;

	    $date = date_create($starts);
	    # Add the recurrance to the original date until the time is in the future
	    while($date->getTimestamp() < time()){
		date_modify($date, $recurs);
	    }

	    # Not sure how the time works on Habitica. Sometimes when editing then
	    # due date it shows up as one day early, but when displayed in the actual
	    # task it appears correctly.
	    $task->date = date("Y-m-d", $date->getTimestamp()); 
	    return $this->habiticaPost('user/tasks', $task);
	}
    }
}

?>
