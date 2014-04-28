Natural
=======
Joomla Package of Natural View.
Also includes other core bits, like com_composite.

HOW TO INSTALL
==============
Use the following line in Joomla "Install from URL"
https://raw.githubusercontent.com/RedSnapper/Natural/master/pkg_natural.zip


MANAGING GIT REPO
=================
This will automatically package anything that needs packaging.
What it won't do is generate a new line in the update.xml file.

````mkdir -p .git/hooks````

````cp git_hooks_pre_commit .git/hooks/pre-commit````

````chmod 777 .git/hooks/pre-commit````

