Author: Rylan Perumal

Multiple User Commits for Git

	- We can have a main user account called "sdpuser",
		this will serve as the repo account which we will clone from since our repo is private
		only people who have access to our repo can make commits. The reason for this
		is that by cloning the repo from the sdp user, we will all know the password, thus
		making a commit to the repo will be efficient.

	- Before making a commit to the repo of the source files you have edited,
	  run the following command in the terminal:

			$ source change_user/"Your name"

			eg : source change_user/jeff

	- This will change the current user so that you can make the commit in your
	  name, to check that this is successful:

			$ git config --list

	You will see at the bottom of the page user.email = " ...", this should
	contain your email associated with your bitbucket account.
	NOTE: This will change "sdpuser" to "yourname" in the commit log.
	Now you are the current owner of the commit.

	Then proceed to making commit and pushing to the repo.
