<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

include_once "./WriteLog.php";

final class WriteLogTest extends TestCase
{
    public function setUp(): void
    {
        $gameName = $GLOBALS["gameName"] = "test";

        mkdir("./Games/$gameName", 0700, true);
    }

    public function tearDown(): void
    {
        global $gameName;
        if (file_exists(LogPath($gameName)))
            unlink(LogPath($gameName));
        rmdir("./Games/$gameName");
    }

    public function testFormatPlayer(): void
    {
        $this->assertEquals(FmtPlayer("test", 1), "<span class='p1-label'>test</span>");
    }

    public function testFormatKeyword(): void
    {
        $this->assertEquals(FmtKeyword("test"), "<span class='keyword'>test</span>");
    }

    public function testLogPath(): void
    {
        global $gameName;
        $this->assertEquals(LogPath($gameName), "./Games/$gameName/gamelog.txt");
    }

    public function testWriteLog(): void
    {
        global $gameName;

        CreateLog($gameName);
        WriteLog($msg = "test log entry");
        EchoLog($gameName);

        $this->assertFileExists(LogPath($gameName));
        $this->expectOutputString("<p class='log-entry'>{$msg}</p>\r\n");
    }

    public function testWriteLogWithPlayerId(): void
    {
        global $gameName;

        CreateLog($gameName);
        WriteLog($msg = "test log entry", 1);
        EchoLog($gameName);

        $this->assertFileExists(LogPath($gameName));
        $this->expectOutputString("<p class='log-entry'><span class='p1-label'>{$msg}</span></p>\r\n");
    }

    public function testJsonLog(): void
    {
        global $gameName;

        CreateLog($gameName);
        WriteLog($msg = "test log entry");

        $this->assertEquals("<p class='log-entry'>{$msg}</p>\r\n", JSONLog($gameName));
    }
}
