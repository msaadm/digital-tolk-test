For this test, I am selecting the following:  

1) `app/Http/Controllers/BookingController.php` - for Refactoring  
2) `App/Helpers/TeHelper.php method willExpireAt` - for Unit Test
  
## Refactoring:
Code needs refactoring when it serves the task but difficult to understand for developers.
As Kyle Simpson said "`Code is for humans (not for machines)`". It must be helpful, wel-defined and easy so other
developer can understand the purpose.
As I can see that in the `BookingController`, there are some repetition or DRY code, we can combine some functions
into one with different parameters.
Also, I think it's missing some basic comments, just to let the fellow team to know that what is the purpose of each
function, Yes some functions names are quite self-explanatory, but other ones are little ambiguous. Another point which
I want to take care of is that, most of the function are accepting all the inputs (`$request->all()`) and pushed it to
some array named `$data`, which in my opinion is not good, we should accept particular inputs which are required, so it
can block any security loop holes, secondly the variable name `$data` is quite general, it doesn't show any relation that
 what we are accepting as an input here like is it a single ID or array of IDs etc.
 
 I am a big fan of _PHP Storm_ as an editor, so I think we should take help from such an editor to at least format our files according to
some standards, because as I can see that there are some random line changes and brackets missing when its single line
conditions (I like to have it, because it explains the code better).

I am searching the file from top to bottom, so I will list the changes when it happened.

NB: Line No. may change, because I am remove extra empty lines.

### Changes:  
1. On `Line#40` I changed the __OR__ condition into `in_array` function, it helps to read when we have more roles to
check with.
2. We should have same parameters order for similar functions Like in `bookingController` `store` method params are 
like `$authenticatedUser` then `$data` for repository function, and in `update` method params are like `$id` (ambiguous),
`$data`, and then `$authenticatedUser`. I am updating it as follows:  
`$this->repository->store($request->__authenticatedUser, $data);`      
`$this->repository->updateJob($job_id, $request->__authenticatedUser, array_except($data, ['_token', 'submit']));`
3. function `immediateJobEmail` `Line#93` has unused variable (`$adminSenderEmail`), so I am removing it
4. Here is a common error in function`getHistory` on `Line#106`, that there is `=` (assignment operator) inside a
`if` (conditional statement) instead of `==` (comparision operator) which leads to all comparison results to true and
sends back the response in all requests. Also we didn't have defined `$user_id` in this function. I think this method
should return History only for the users who are requesting, so they can't request history of any other users, Obviously
this condition should not apply on Admin/Super Roles, so I am modifying it as I describe it.
5. As I have an understanding, for Refactoring the `BookingController`, I must have to go through to `BookingRepository`
time to time, so what I found is that Our 2 functions `acceptJob` and `acceptJobWithId` are quite identical, so I am 
removing the second one and modifying the first one (`acceptJob`). I am not quite sure that I can do this for `acceptJob`,
 but I prefer that function which receives Entity ID should receive it as function parameter, it helps to maintain
 REST structure.
6. Redirecting all these methods `acceptJob`, `cancelJob`, `endJob` and `customerNotCall` to a new
 function `updateJobStatus` with `job_id` and `status_id`. Because I am not sure about how the given functions are called, maybe they are connected
 to some routes directly as endpoints, if they don't then I'll remove all of these with my new function, but for now I
 am going with the first approach.
 7. Combining 2 functions `resendNotifications` and `resendSMSNotifications` with same approach as updateJob Status, but
 this time I am assuming that they are not endpoints, so I am removing the second function and modifying the first one.
8. In function `distanceFeed` it will always response back as `Record Updated`, but I think it will only response back
when one of the last 2 conditions full filled, so I am fixing it. Also if PHP > 7.0 we can use
`null coalescing operator (??)` which is doing the same thing for comparing array index that it has a value or not
in one line of code.

### Ignored:
1. We can merge `store` and `update` function into one, but it will make things complex, and most of the time they
are our endpoints for api calls so let them separate although they may have some repetitive code.
2. I am not working in `BookingRepository` but what I found in that file is that in some functions (`store`) they 
receive `$data` as an input and then function is modifying the same input variable, which should not be acceptable,
we must declare some new variables, so we don't accidentally changed our input parameters.
3. Although I must update the `$data` variable to something meaningful and which accepts only the required parameters
but that required much more understanding of application, work and time required with the `BookingRepository` that's why I am not
doing it for now. 

## Unit Test:
We do unit testing to test individual parts of our application, as they work correctly or as we want them to work. For
this I am testing `willExpireAt` of class `TelHelper`. As we can see this method is comparing 2 `DateTime` objects and 
returns a datetime.

We have 4 conditions here, so we have to create assertion for each of them.
1. `$difference <= 24`
2. `24 < $difference <= 72`
3. `72 < $difference <= 90`
4. `90 < $difference`

I took some help from my custom PHP file which uses carbon and give me the desired results in which each condition can
be true, so I can put in my assert results.

But what I found that `WillExpireAt` is having a logical error by having the `<= 90` condition as the first one, which
removes the possibility for being conditions 2 and 3 as true. 
So I also fixed that in code file by rearranging it.

I am not able to Run the test, but I tried to make it as clear as possible (maybe couple includes/tweaks required to 
make it executable).