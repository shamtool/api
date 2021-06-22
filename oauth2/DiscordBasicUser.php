<?php

class DiscordBasicUser {
    /** the user's id - Snowflake */
    public string $id;
    /** the user's username, not unique across the platform */
    public string $username;
    /** the user's 4-digit discord-tag */
    public string $discriminator;
}
