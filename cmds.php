<?php 
/* helpful shell commands */

//Git

    // Git Repo Found
    $git_found              = shell_exec( "[ -d .git ] && echo 'true'" );

    // Detect local changes
    $git_has_local_changes  = shell_exec( "git status --p" );

    // Detect detached HEAD
    $git_has_detatched_head = shell_exec( "git symbolic-ref -q HEAD" );


    // Head
    $git_head               = shell_exec( "cat .git/HEAD" );

    // Current Branch
    $git_current_branch     = shell_exec( "git branch" );

    // Last Fetch Time
    $git_last_fetch_epoch   = shell_exec( "stat -c %Y .git/FETCH_HEAD" );

?>