<?php
/**
     * sanitize input string for prevention of xss attack
     */
    function sanitizestring($input)
    {        
        return  htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }