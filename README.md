Natural
=======
Joomla Package of Natural View.
Also includes other core bits, like com_composite.

HOW TO INSTALL
==============
Each folder needs to be zipped up separately.
Then all the zipped folders need to be zipped up with pkg_natural.xml

The resulting zipfile is the package.
Upload it using /administrator/index.php?option=com_installer

MANAGING GIT REPO
=================
mkdir -p .git/hooks
cp git_hooks_pre_commit .git/hooks/pre-commit
chmod 777 .git/hooks/pre-commit
