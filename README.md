# Habitica_PHP_API
A PHP class to interact with the Habitica (formerly HabitRPG) API

This class implements some extra features that go beyond the normal functionality of Habitica.

For example, you can create tasks that recur at intervals that are not available on Habitica (e.g. once per month).

## Using the API

The API needs to know who you are, so first find your user-id and api-key by going to Settings->API in the Habitica dashboard.

The recommended way of setting the user-id and api-key is with environment variables. This will ensure that your user-id and api-key do not get uploaded to GitHub.

### Create a new Habitica instance
```php
$hb = new Habitica(getenv('HABITICA_USER_ID'), getenv('HABITICA_API_KEY'));
```

### Creating a recurring task
Use the ```php createRecurringTask``` function to add a task that will recur at a given interval.

For example, if you want the todo to recur automatically each week then use
$hb->createRecurringTask('Weekly task', '2015-09-01', '+1 week', 'todo')

Although the option of a weekly todo is already in Habitica, this particular version has some advantages:
* The recurrance happens on the dueDate, which means that you will not be penalized for not doing the task on a particular day. This could be useful if you needed to have a task that needs to be done once a week, but it is not a big deal if it happens a few days late.
* The recurrance can happen at intervals that are not available to Habitica. For example, you could set '+2 weeks', or '+1 month' for the recurrance.

### Notes:
* The recurrance can act funny if you set it to recur monthly and have the day set to the 29th, 30th, or 31st of a month. The problem is that adding 1 month to Jan 30 results in March 2st, which might not be the expected behaviour. This propagages when the month is added multiple times so a task that began on 2010-11-30 will become 2015-10-02 if the current date is 2015-09-09. **If anyone knows a nice way to implement this feature I'd be happy to hear it!**

## Using the api automatically
If you are on linux then you can use cron jobs to automatically run a script that creates/updates your recurring tasks.
Just create a script that has the recurring task in it such as
```php
$hb = new Habitica(getenv('HABITICA_USER_ID'), getenv('HABITICA_API_KEY'));
$hb->createRecurringTask('Weekly task', '2015-09-01', '+1 week', 'todo')
```

Assuming that you load the environment variables from .profile, then add the following line to your crontab by running ```crontab -e``` and adding:
```
0 * * * * . $HOME/.profile; php $HOME/<path_to_script>
```
This will run the script once an hour while the computer is on. The createRecurringTask function is designed to make sure that the event is not added again if the event already exists.