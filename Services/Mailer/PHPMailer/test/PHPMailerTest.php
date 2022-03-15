<?php

/**
 * PHPMailer - PHP email transport unit tests.
 * PHP version 5.5.
 *
 * @author    Marcus Bointon <phpmailer@synchromedia.co.uk>
 * @author    Andy Prevost
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2004 - 2009 Andy Prevost
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace PHPMailer\Test;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\POP3;
use PHPMailer\PHPMailer\SMTP;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * PHPMailer - PHP email transport unit test class.
 */
final class PHPMailerTest extends TestCase
{
    /**
     * Holds the PHPMailer instance.
     *
     * @var PHPMailer
     */
    private $Mail;

    /**
     * Holds the SMTP mail host.
     *
     * @var string
     */
    private $Host = '';

    /**
     * Holds the change log.
     *
     * @var string[]
     */
    private $ChangeLog = [];

    /**
     * Holds the note log.
     *
     * @var string[]
     */
    private $NoteLog = [];

    /**
     * Default include path.
     *
     * @var string
     */
    private $INCLUDE_DIR = '..';

    /**
     * PIDs of any processes we need to kill.
     *
     * @var array
     */
    private $pids = [];

    /**
     * Run before each test is started.
     */
    protected function set_up()
    {
        $this->INCLUDE_DIR = dirname(__DIR__); //Default to the dir above the test dir, i.e. the project home dir
        if (file_exists($this->INCLUDE_DIR . '/test/testbootstrap.php')) {
            include $this->INCLUDE_DIR . '/test/testbootstrap.php'; //Overrides go in here
        }
        $this->Mail = new PHPMailer();
        $this->Mail->SMTPDebug = SMTP::DEBUG_CONNECTION; //Full debug output
        $this->Mail->Debugoutput = ['PHPMailer\Test\DebugLogTestListener', 'debugLog'];
        $this->Mail->Priority = 3;
        $this->Mail->Encoding = '8bit';
        $this->Mail->CharSet = PHPMailer::CHARSET_ISO88591;
        if (array_key_exists('mail_from', $_REQUEST)) {
            $this->Mail->From = $_REQUEST['mail_from'];
        } else {
            $this->Mail->From = 'unit_test@phpmailer.example.com';
        }
        $this->Mail->FromName = 'Unit Tester';
        $this->Mail->Sender = '';
        $this->Mail->Subject = 'Unit Test';
        $this->Mail->Body = '';
        $this->Mail->AltBody = '';
        $this->Mail->WordWrap = 0;
        if (array_key_exists('mail_host', $_REQUEST)) {
            $this->Mail->Host = $_REQUEST['mail_host'];
        } else {
            $this->Mail->Host = 'mail.example.com';
        }
        if (array_key_exists('mail_port', $_REQUEST)) {
            $this->Mail->Port = $_REQUEST['mail_port'];
        } else {
            $this->Mail->Port = 25;
        }
        $this->Mail->Helo = 'localhost.localdomain';
        $this->Mail->SMTPAuth = false;
        $this->Mail->Username = '';
        $this->Mail->Password = '';
        if (array_key_exists('mail_useauth', $_REQUEST)) {
            $this->Mail->SMTPAuth = $_REQUEST['mail_useauth'];
        }
        if (array_key_exists('mail_username', $_REQUEST)) {
            $this->Mail->Username = $_REQUEST['mail_username'];
        }
        if (array_key_exists('mail_userpass', $_REQUEST)) {
            $this->Mail->Password = $_REQUEST['mail_userpass'];
        }
        $this->Mail->addReplyTo('no_reply@phpmailer.example.com', 'Reply Guy');
        $this->Mail->Sender = 'unit_test@phpmailer.example.com';
        if ($this->Mail->Host != '') {
            $this->Mail->isSMTP();
        } else {
            $this->Mail->isMail();
        }
        if (array_key_exists('mail_to', $_REQUEST)) {
            $this->setAddress($_REQUEST['mail_to'], 'Test User', 'to');
        }
        if (array_key_exists('mail_cc', $_REQUEST) && $_REQUEST['mail_cc'] !== '') {
            $this->setAddress($_REQUEST['mail_cc'], 'Carbon User', 'cc');
        }
    }

    /**
     * Run after each test is completed.
     */
    protected function tear_down()
    {
        //Clean global variables
        $this->Mail = null;
        $this->ChangeLog = [];
        $this->NoteLog = [];

        foreach ($this->pids as $pid) {
            $p = escapeshellarg($pid);
            shell_exec("ps $p && kill -TERM $p");
        }
    }

    /**
     * Build the body of the message in the appropriate format.
     */
    private function buildBody()
    {
        $this->checkChanges();

        //Determine line endings for message
        if ('text/html' === $this->Mail->ContentType || $this->Mail->AltBody !== '') {
            $eol = "<br>\r\n";
            $bullet_start = '<li>';
            $bullet_end = "</li>\r\n";
            $list_start = "<ul>\r\n";
            $list_end = "</ul>\r\n";
        } else {
            $eol = "\r\n";
            $bullet_start = ' - ';
            $bullet_end = "\r\n";
            $list_start = '';
            $list_end = '';
        }

        $ReportBody = '';

        $ReportBody .= '---------------------' . $eol;
        $ReportBody .= 'Unit Test Information' . $eol;
        $ReportBody .= '---------------------' . $eol;
        $ReportBody .= 'phpmailer version: ' . PHPMailer::VERSION . $eol;
        $ReportBody .= 'Content Type: ' . $this->Mail->ContentType . $eol;
        $ReportBody .= 'CharSet: ' . $this->Mail->CharSet . $eol;

        if ($this->Mail->Host !== '') {
            $ReportBody .= 'Host: ' . $this->Mail->Host . $eol;
        }

        //If attachments then create an attachment list
        $attachments = $this->Mail->getAttachments();
        if (count($attachments) > 0) {
            $ReportBody .= 'Attachments:' . $eol;
            $ReportBody .= $list_start;
            foreach ($attachments as $attachment) {
                $ReportBody .= $bullet_start . 'Name: ' . $attachment[1] . ', ';
                $ReportBody .= 'Encoding: ' . $attachment[3] . ', ';
                $ReportBody .= 'Type: ' . $attachment[4] . $bullet_end;
            }
            $ReportBody .= $list_end . $eol;
        }

        //If there are changes then list them
        if (count($this->ChangeLog) > 0) {
            $ReportBody .= 'Changes' . $eol;
            $ReportBody .= '-------' . $eol;

            $ReportBody .= $list_start;
            foreach ($this->ChangeLog as $iValue) {
                $ReportBody .= $bullet_start . $iValue[0] . ' was changed to [' .
                    $iValue[1] . ']' . $bullet_end;
            }
            $ReportBody .= $list_end . $eol . $eol;
        }

        //If there are notes then list them
        if (count($this->NoteLog) > 0) {
            $ReportBody .= 'Notes' . $eol;
            $ReportBody .= '-----' . $eol;

            $ReportBody .= $list_start;
            foreach ($this->NoteLog as $iValue) {
                $ReportBody .= $bullet_start . $iValue . $bullet_end;
            }
            $ReportBody .= $list_end;
        }

        //Re-attach the original body
        $this->Mail->Body .= $eol . $ReportBody;
    }

    /**
     * Check which default settings have been changed for the report.
     */
    private function checkChanges()
    {
        if (3 != $this->Mail->Priority) {
            $this->addChange('Priority', $this->Mail->Priority);
        }
        if (PHPMailer::ENCODING_8BIT !== $this->Mail->Encoding) {
            $this->addChange('Encoding', $this->Mail->Encoding);
        }
        if (PHPMailer::CHARSET_ISO88591 !== $this->Mail->CharSet) {
            $this->addChange('CharSet', $this->Mail->CharSet);
        }
        if ('' != $this->Mail->Sender) {
            $this->addChange('Sender', $this->Mail->Sender);
        }
        if (0 != $this->Mail->WordWrap) {
            $this->addChange('WordWrap', $this->Mail->WordWrap);
        }
        if ('mail' !== $this->Mail->Mailer) {
            $this->addChange('Mailer', $this->Mail->Mailer);
        }
        if (25 != $this->Mail->Port) {
            $this->addChange('Port', $this->Mail->Port);
        }
        if ('localhost.localdomain' !== $this->Mail->Helo) {
            $this->addChange('Helo', $this->Mail->Helo);
        }
        if ($this->Mail->SMTPAuth) {
            $this->addChange('SMTPAuth', 'true');
        }
    }

    /**
     * Add a changelog entry.
     *
     * @param string $sName
     * @param string $sNewValue
     */
    private function addChange($sName, $sNewValue)
    {
        $this->ChangeLog[] = [$sName, $sNewValue];
    }

    /**
     * Adds a simple note to the message.
     *
     * @param string $sValue
     */
    private function addNote($sValue)
    {
        $this->NoteLog[] = $sValue;
    }

    /**
     * Adds all of the addresses.
     *
     * @param string $sAddress
     * @param string $sName
     * @param string $sType
     *
     * @return bool
     */
    private function setAddress($sAddress, $sName = '', $sType = 'to')
    {
        switch ($sType) {
            case 'to':
                return $this->Mail->addAddress($sAddress, $sName);
            case 'cc':
                return $this->Mail->addCC($sAddress, $sName);
            case 'bcc':
                return $this->Mail->addBCC($sAddress, $sName);
        }

        return false;
    }

    /**
     * Check that we have loaded default test params.
     * Pretty much everything will fail due to unset recipient if this is not done.
     */
    public function testBootstrap()
    {
        self::assertFileExists(
            $this->INCLUDE_DIR . '/test/testbootstrap.php',
            'Test config params missing - copy testbootstrap.php to testbootstrap-dist.php and change as appropriate'
        );
    }

    /**
     * Test CRAM-MD5 authentication.
     * Needs a connection to a server that supports this auth mechanism, so commented out by default.
     */
    public function testAuthCRAMMD5()
    {
        $this->markTestIncomplete(
            'Test needs a connection to a server supporting the CRAMMD5 auth mechanism.'
        );

        $this->Mail->Host = 'hostname';
        $this->Mail->Port = 587;
        $this->Mail->SMTPAuth = true;
        $this->Mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->Mail->AuthType = 'CRAM-MD5';
        $this->Mail->Username = 'username';
        $this->Mail->Password = 'password';
        $this->Mail->Body = 'Test body';
        $this->Mail->Subject .= ': Auth CRAM-MD5';
        $this->Mail->From = 'from@example.com';
        $this->Mail->Sender = 'from@example.com';
        $this->Mail->clearAllRecipients();
        $this->Mail->addAddress('user@example.com');
        //self::assertTrue($this->mail->send(), $this->mail->ErrorInfo);
    }

    /**
     * Test email address validation.
     * Test addresses obtained from http://isemail.info
     * Some failing cases commented out that are apparently up for debate!
     */
    public function testValidate()
    {
        $validaddresses = [
            'first@example.org',
            'first.last@example.org',
            '1234567890123456789012345678901234567890123456789012345678901234@example.org',
            '"first\"last"@example.org',
            '"first@last"@example.org',
            '"first\last"@example.org',
            'first.last@[12.34.56.78]',
            'first.last@x23456789012345678901234567890123456789012345678901234567890123.example.org',
            'first.last@123.example.org',
            '"first\last"@example.org',
            '"Abc\@def"@example.org',
            '"Fred\ Bloggs"@example.org',
            '"Joe.\Blow"@example.org',
            '"Abc@def"@example.org',
            'user+mailbox@example.org',
            'customer/department=shipping@example.org',
            '$A12345@example.org',
            '!def!xyz%abc@example.org',
            '_somename@example.org',
            'dclo@us.example.com',
            'peter.piper@example.org',
            'test@example.org',
            'TEST@example.org',
            '1234567890@example.org',
            'test+test@example.org',
            'test-test@example.org',
            't*est@example.org',
            '+1~1+@example.org',
            '{_test_}@example.org',
            'test.test@example.org',
            '"test.test"@example.org',
            'test."test"@example.org',
            '"test@test"@example.org',
            'test@123.123.123.x123',
            'test@[123.123.123.123]',
            'test@example.example.org',
            'test@example.example.example.org',
            '"test\test"@example.org',
            '"test\blah"@example.org',
            '"test\blah"@example.org',
            '"test\"blah"@example.org',
            'customer/department@example.org',
            '_Yosemite.Sam@example.org',
            '~@example.org',
            '"Austin@Powers"@example.org',
            'Ima.Fool@example.org',
            '"Ima.Fool"@example.org',
            '"first"."last"@example.org',
            '"first".middle."last"@example.org',
            '"first".last@example.org',
            'first."last"@example.org',
            '"first"."middle"."last"@example.org',
            '"first.middle"."last"@example.org',
            '"first.middle.last"@example.org',
            '"first..last"@example.org',
            '"first\"last"@example.org',
            'first."mid\dle"."last"@example.org',
            'name.lastname@example.com',
            'a@example.com',
            'aaa@[123.123.123.123]',
            'a-b@example.com',
            '+@b.c',
            '+@b.com',
            'a@b.co-foo.uk',
            'valid@about.museum',
            'shaitan@my-domain.thisisminekthx',
            '"Joe\Blow"@example.org',
            'user%uucp!path@example.edu',
            'cdburgess+!#$%&\'*-/=?+_{}|~test@example.com',
            'test@test.com',
            'test@xn--example.com',
            'test@example.com',
        ];
        //These are invalid according to PHP's filter_var
        //which doesn't allow dotless domains, numeric TLDs or unbracketed IPv4 literals
        $invalidphp = [
            'a@b',
            'a@bar',
            'first.last@com',
            'test@123.123.123.123',
            'foobar@192.168.0.1',
            'first.last@example.123',
        ];
        //Valid RFC 5322 addresses using quoting and comments
        //Note that these are *not* all valid for RFC5321
        $validqandc = [
            'HM2Kinsists@(that comments are allowed)this.is.ok',
            '"Doug \"Ace\" L."@example.org',
            '"[[ test ]]"@example.org',
            '"Ima Fool"@example.org',
            '"test blah"@example.org',
            '(foo)cal(bar)@(baz)example.com(quux)',
            'cal@example(woo).(yay)com',
            'cal(woo(yay)hoopla)@example.com',
            'cal(foo\@bar)@example.com',
            'cal(foo\)bar)@example.com',
            'first().last@example.org',
            'pete(his account)@silly.test(his host)',
            'c@(Chris\'s host.)public.example',
            'jdoe@machine(comment). example',
            '1234 @ local(blah) .machine .example',
            'first(abc.def).last@example.org',
            'first(a"bc.def).last@example.org',
            'first.(")middle.last(")@example.org',
            'first(abc\(def)@example.org',
            'first.last@x(1234567890123456789012345678901234567890123456789012345678901234567890).com',
            'a(a(b(c)d(e(f))g)h(i)j)@example.org',
            '"hello my name is"@example.com',
            '"Test \"Fail\" Ing"@example.org',
            'first.last @example.org',
        ];
        //Valid explicit IPv6 numeric addresses
        $validipv6 = [
            'first.last@[IPv6:::a2:a3:a4:b1:b2:b3:b4]',
            'first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3::]',
            'first.last@[IPv6:::]',
            'first.last@[IPv6:::b4]',
            'first.last@[IPv6:::b3:b4]',
            'first.last@[IPv6:a1::b4]',
            'first.last@[IPv6:a1::]',
            'first.last@[IPv6:a1:a2::]',
            'first.last@[IPv6:0123:4567:89ab:cdef::]',
            'first.last@[IPv6:0123:4567:89ab:CDEF::]',
            'first.last@[IPv6:::a3:a4:b1:ffff:11.22.33.44]',
            'first.last@[IPv6:::a2:a3:a4:b1:ffff:11.22.33.44]',
            'first.last@[IPv6:a1:a2:a3:a4::11.22.33.44]',
            'first.last@[IPv6:a1:a2:a3:a4:b1::11.22.33.44]',
            'first.last@[IPv6:a1::11.22.33.44]',
            'first.last@[IPv6:a1:a2::11.22.33.44]',
            'first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.44]',
            'first.last@[IPv6:0123:4567:89ab:CDEF::11.22.33.44]',
            'first.last@[IPv6:a1::b2:11.22.33.44]',
            'first.last@[IPv6:::12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333::4444:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:12.34.56.78]',
            'first.last@[IPv6:::1111:2222:3333:4444:5555:6666]',
            'first.last@[IPv6:1111:2222:3333::4444:5555:6666]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666::]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:8888]',
            'first.last@[IPv6:1111:2222:3333::4444:5555:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333::4444:5555:6666:7777]',
        ];
        $invalidaddresses = [
            'first.last@sub.do,com',
            'first\@last@iana.org',
            '123456789012345678901234567890123456789012345678901234567890' .
            '@12345678901234567890123456789012345678901234 [...]',
            'first.last',
            '12345678901234567890123456789012345678901234567890123456789012345@iana.org',
            '.first.last@iana.org',
            'first.last.@iana.org',
            'first..last@iana.org',
            '"first"last"@iana.org',
            '"""@iana.org',
            '"\"@iana.org',
            //'""@iana.org',
            'first\@last@iana.org',
            'first.last@',
            'x@x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.' .
            'x23456789.x23456789.x23456789.x23 [...]',
            'first.last@[.12.34.56.78]',
            'first.last@[12.34.56.789]',
            'first.last@[::12.34.56.78]',
            'first.last@[IPv5:::12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:8888:9999]',
            'first.last@[IPv6:1111:2222::3333::4444:5555:6666]',
            'first.last@[IPv6:1111:2222:333x::4444:5555]',
            'first.last@[IPv6:1111:2222:33333::4444:5555]',
            'first.last@-xample.com',
            'first.last@exampl-.com',
            'first.last@x234567890123456789012345678901234567890123456789012345678901234.iana.org',
            'abc\@def@iana.org',
            'abc\@iana.org',
            'Doug\ \"Ace\"\ Lovell@iana.org',
            'abc@def@iana.org',
            'abc\@def@iana.org',
            'abc\@iana.org',
            '@iana.org',
            'doug@',
            '"qu@iana.org',
            'ote"@iana.org',
            '.dot@iana.org',
            'dot.@iana.org',
            'two..dot@iana.org',
            '"Doug "Ace" L."@iana.org',
            'Doug\ \"Ace\"\ L\.@iana.org',
            'hello world@iana.org',
            //'helloworld@iana .org',
            'gatsby@f.sc.ot.t.f.i.tzg.era.l.d.',
            'test.iana.org',
            'test.@iana.org',
            'test..test@iana.org',
            '.test@iana.org',
            'test@test@iana.org',
            'test@@iana.org',
            '-- test --@iana.org',
            '[test]@iana.org',
            '"test"test"@iana.org',
            '()[]\;:,><@iana.org',
            'test@.',
            'test@example.',
            'test@.org',
            'test@12345678901234567890123456789012345678901234567890123456789012345678901234567890' .
            '12345678901234567890 [...]',
            'test@[123.123.123.123',
            'test@123.123.123.123]',
            'NotAnEmail',
            '@NotAnEmail',
            '"test"blah"@iana.org',
            '.wooly@iana.org',
            'wo..oly@iana.org',
            'pootietang.@iana.org',
            '.@iana.org',
            'Ima Fool@iana.org',
            'phil.h\@\@ck@haacked.com',
            'foo@[\1.2.3.4]',
            //'first."".last@iana.org',
            'first\last@iana.org',
            'Abc\@def@iana.org',
            'Fred\ Bloggs@iana.org',
            'Joe.\Blow@iana.org',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:12.34.567.89]',
            '{^c\@**Dog^}@cartoon.com',
            //'"foo"(yay)@(hoopla)[1.2.3.4]',
            'cal(foo(bar)@iamcal.com',
            'cal(foo)bar)@iamcal.com',
            'cal(foo\)@iamcal.com',
            'first(12345678901234567890123456789012345678901234567890)last@(1234567890123456789' .
            '01234567890123456789012 [...]',
            'first(middle)last@iana.org',
            'first(abc("def".ghi).mno)middle(abc("def".ghi).mno).last@(abc("def".ghi).mno)example' .
            '(abc("def".ghi).mno). [...]',
            'a(a(b(c)d(e(f))g)(h(i)j)@iana.org',
            '.@',
            '@bar.com',
            '@@bar.com',
            'aaa.com',
            'aaa@.com',
            'aaa@.123',
            'aaa@[123.123.123.123]a',
            'aaa@[123.123.123.333]',
            'a@bar.com.',
            'a@-b.com',
            'a@b-.com',
            '-@..com',
            '-@a..com',
            'invalid@about.museum-',
            'test@...........com',
            '"Unicode NULL' . chr(0) . '"@char.com',
            'Unicode NULL' . chr(0) . '@char.com',
            'first.last@[IPv6::]',
            'first.last@[IPv6::::]',
            'first.last@[IPv6::b4]',
            'first.last@[IPv6::::b4]',
            'first.last@[IPv6::b3:b4]',
            'first.last@[IPv6::::b3:b4]',
            'first.last@[IPv6:a1:::b4]',
            'first.last@[IPv6:a1:]',
            'first.last@[IPv6:a1:::]',
            'first.last@[IPv6:a1:a2:]',
            'first.last@[IPv6:a1:a2:::]',
            'first.last@[IPv6::11.22.33.44]',
            'first.last@[IPv6::::11.22.33.44]',
            'first.last@[IPv6:a1:11.22.33.44]',
            'first.last@[IPv6:a1:::11.22.33.44]',
            'first.last@[IPv6:a1:a2:::11.22.33.44]',
            'first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.xx]',
            'first.last@[IPv6:0123:4567:89ab:CDEFF::11.22.33.44]',
            'first.last@[IPv6:a1::a4:b1::b4:11.22.33.44]',
            'first.last@[IPv6:a1::11.22.33]',
            'first.last@[IPv6:a1::11.22.33.44.55]',
            'first.last@[IPv6:a1::b211.22.33.44]',
            'first.last@[IPv6:a1::b2::11.22.33.44]',
            'first.last@[IPv6:a1::b3:]',
            'first.last@[IPv6::a2::b4]',
            'first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3:]',
            'first.last@[IPv6::a2:a3:a4:b1:b2:b3:b4]',
            'first.last@[IPv6:a1:a2:a3:a4::b1:b2:b3:b4]',
            //This is a valid RFC5322 address, but we don't want to allow it for obvious reasons!
            "(\r\n RCPT TO:user@example.com\r\n DATA \\\nSubject: spam10\\\n\r\n Hello," .
            "\r\n this is a spam mail.\\\n.\r\n QUIT\r\n ) a@example.net",
        ];
        //IDNs in Unicode and ASCII forms.
        $unicodeaddresses = [
            'first.last@bücher.ch',
            'first.last@кто.рф',
            'first.last@phplíst.com',
        ];
        $asciiaddresses = [
            'first.last@xn--bcher-kva.ch',
            'first.last@xn--j1ail.xn--p1ai',
            'first.last@xn--phplst-6va.com',
        ];
        $goodfails = [];
        foreach (array_merge($validaddresses, $asciiaddresses) as $address) {
            if (!PHPMailer::validateAddress($address)) {
                $goodfails[] = $address;
            }
        }
        $badpasses = [];
        foreach (array_merge($invalidaddresses, $unicodeaddresses) as $address) {
            if (PHPMailer::validateAddress($address)) {
                $badpasses[] = $address;
            }
        }
        $err = '';
        if (count($goodfails) > 0) {
            $err .= "Good addresses that failed validation:\n";
            $err .= implode("\n", $goodfails);
        }
        if (count($badpasses) > 0) {
            if (!empty($err)) {
                $err .= "\n\n";
            }
            $err .= "Bad addresses that passed validation:\n";
            $err .= implode("\n", $badpasses);
        }
        self::assertEmpty($err, $err);
        //For coverage
        self::assertTrue(PHPMailer::validateAddress('test@example.com', 'auto'));
        self::assertFalse(PHPMailer::validateAddress('test@example.com.', 'auto'));
        self::assertTrue(PHPMailer::validateAddress('test@example.com', 'pcre'));
        self::assertFalse(PHPMailer::validateAddress('test@example.com.', 'pcre'));
        self::assertTrue(PHPMailer::validateAddress('test@example.com', 'pcre8'));
        self::assertFalse(PHPMailer::validateAddress('test@example.com.', 'pcre8'));
        self::assertTrue(PHPMailer::validateAddress('test@example.com', 'html5'));
        self::assertFalse(PHPMailer::validateAddress('test@example.com.', 'html5'));
        self::assertTrue(PHPMailer::validateAddress('test@example.com', 'php'));
        self::assertFalse(PHPMailer::validateAddress('test@example.com.', 'php'));
        self::assertTrue(PHPMailer::validateAddress('test@example.com', 'noregex'));
        self::assertFalse(PHPMailer::validateAddress('bad', 'noregex'));
    }

    /**
     * Test injecting a custom validator.
     */
    public function testCustomValidator()
    {
        //Inject a one-off custom validator
        self::assertTrue(
            PHPMailer::validateAddress(
                'user@example.com',
                function ($address) {
                    return strpos($address, '@') !== false;
                }
            ),
            'Custom validator false negative'
        );
        self::assertFalse(
            PHPMailer::validateAddress(
                'userexample.com',
                function ($address) {
                    return strpos($address, '@') !== false;
                }
            ),
            'Custom validator false positive'
        );
        //Set the default validator to an injected function
        PHPMailer::$validator = function ($address) {
            return 'user@example.com' === $address;
        };
        self::assertTrue(
            $this->Mail->addAddress('user@example.com'),
            'Custom default validator false negative'
        );
        self::assertFalse(
        //Need to pick a failing value which would pass all other validators
        //to be sure we're using our custom one
            $this->Mail->addAddress('bananas@example.com'),
            'Custom default validator false positive'
        );
        //Set default validator to PHP built-in
        PHPMailer::$validator = 'php';
        self::assertFalse(
        //This is a valid address that FILTER_VALIDATE_EMAIL thinks is invalid
            $this->Mail->addAddress('first.last@example.123'),
            'PHP validator not behaving as expected'
        );
    }

    /**
     * Word-wrap an ASCII message.
     */
    public function testWordWrap()
    {
        $this->Mail->WordWrap = 40;
        $my_body = str_repeat(
            'Here is the main body of this message.  It should ' .
            'be quite a few lines.  It should be wrapped at ' .
            '40 characters.  Make sure that it is. ',
            10
        );
        $nBodyLen = strlen($my_body);
        $my_body .= "\n\nThis is the above body length: " . $nBodyLen;

        $this->Mail->Body = $my_body;
        $this->Mail->Subject .= ': Wordwrap';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Word-wrap a multibyte message.
     */
    public function testWordWrapMultibyte()
    {
        $this->Mail->WordWrap = 40;
        $my_body = str_repeat(
            '飛兒樂 團光茫 飛兒樂 團光茫 飛兒樂 團光茫 飛兒樂 團光茫 ' .
            '飛飛兒樂 團光茫兒樂 團光茫飛兒樂 團光飛兒樂 團光茫飛兒樂 團光茫兒樂 團光茫 ' .
            '飛兒樂 團光茫飛兒樂 團飛兒樂 團光茫光茫飛兒樂 團光茫. ',
            10
        );
        $nBodyLen = strlen($my_body);
        $my_body .= "\n\nThis is the above body length: " . $nBodyLen;

        $this->Mail->Body = $my_body;
        $this->Mail->Subject .= ': Wordwrap multibyte';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test low priority.
     */
    public function testLowPriority()
    {
        $this->Mail->Priority = 5;
        $this->Mail->Body = 'Here is the main body.  There should be ' .
            'a reply to address in this message.';
        $this->Mail->Subject .= ': Low Priority';
        $this->Mail->addReplyTo('nobody@nobody.com', 'Nobody (Unit Test)');

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple plain file attachment test.
     */
    public function testMultiplePlainFileAttachment()
    {
        $this->Mail->Body = 'Here is the text body';
        $this->Mail->Subject .= ': Plain + Multiple FileAttachments';

        if (!$this->Mail->addAttachment(realpath($this->INCLUDE_DIR . '/examples/images/phpmailer.png'))) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        if (!$this->Mail->addAttachment(__FILE__, 'test.txt')) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Rejection of non-local file attachments test.
     */
    public function testRejectNonLocalFileAttachment()
    {
        self::assertFalse(
            $this->Mail->addAttachment('https://github.com/PHPMailer/PHPMailer/raw/master/README.md'),
            'addAttachment should reject remote URLs'
        );

        self::assertFalse(
            $this->Mail->addAttachment('phar://phar.php'),
            'addAttachment should reject phar resources'
        );
    }

    /**
     * Simple plain string attachment test.
     */
    public function testPlainStringAttachment()
    {
        $this->Mail->Body = 'Here is the text body';
        $this->Mail->Subject .= ': Plain + StringAttachment';

        $sAttachment = 'These characters are the content of the ' .
            "string attachment.\nThis might be taken from a " .
            'database or some other such thing. ';

        $this->Mail->addStringAttachment($sAttachment, 'string_attach.txt');

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Plain quoted-printable message.
     */
    public function testQuotedPrintable()
    {
        $this->Mail->Body = 'Here is the main body';
        $this->Mail->Subject .= ': Plain + Quoted-printable';
        $this->Mail->Encoding = 'quoted-printable';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Check that a quoted printable encode and decode results in the same as went in
        $t = file_get_contents(__FILE__); //Use this file as test content
        //Force line breaks to UNIX-style
        $t = str_replace(["\r\n", "\r"], "\n", $t);
        self::assertEquals(
            $t,
            quoted_printable_decode($this->Mail->encodeQP($t)),
            'Quoted-Printable encoding round-trip failed'
        );
        //Force line breaks to Windows-style
        $t = str_replace("\n", "\r\n", $t);
        self::assertEquals(
            $t,
            quoted_printable_decode($this->Mail->encodeQP($t)),
            'Quoted-Printable encoding round-trip failed (Windows line breaks)'
        );
    }

    /**
     * Test header encoding & folding.
     */
    public function testHeaderEncoding()
    {
        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
        $letter = html_entity_decode('&eacute;', ENT_COMPAT, PHPMailer::CHARSET_UTF8);
        //This should select B-encoding automatically and should fold
        $bencode = str_repeat($letter, PHPMailer::STD_LINE_LENGTH + 1);
        //This should select Q-encoding automatically and should fold
        $qencode = str_repeat('e', PHPMailer::STD_LINE_LENGTH) . $letter;
        //This should select B-encoding automatically and should not fold
        $bencodenofold = str_repeat($letter, 10);
        //This should select Q-encoding automatically and should not fold
        $qencodenofold = str_repeat('e', 9) . $letter;
        //This should Q-encode as ASCII and fold (previously, this did not encode)
        $longheader = str_repeat('e', PHPMailer::STD_LINE_LENGTH + 10);
        //This should Q-encode as UTF-8 and fold
        $longutf8 = str_repeat($letter, PHPMailer::STD_LINE_LENGTH + 10);
        //This should not change
        $noencode = 'eeeeeeeeee';
        $this->Mail->isMail();
        //Expected results

        $bencoderes = '=?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
            PHPMailer::getLE() .
            ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
            PHPMailer::getLE() .
            ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
            PHPMailer::getLE() .
            ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqQ==?=';
        $qencoderes = '=?utf-8?Q?eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee?=' .
            PHPMailer::getLE() .
            ' =?utf-8?Q?eeeeeeeeeeeeeeeeeeeeeeeeee=C3=A9?=';
        $bencodenofoldres = '=?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6k=?=';
        $qencodenofoldres = '=?utf-8?Q?eeeeeeeee=C3=A9?=';
        $longheaderres = '=?us-ascii?Q?eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee?=' .
            PHPMailer::getLE() . ' =?us-ascii?Q?eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee?=';
        $longutf8res = '=?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
             PHPMailer::getLE() . ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
             PHPMailer::getLE() . ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
             PHPMailer::getLE() . ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqQ==?=';
        $noencoderes = 'eeeeeeeeee';
        self::assertEquals(
            $bencoderes,
            $this->Mail->encodeHeader($bencode),
            'Folded B-encoded header value incorrect'
        );
        self::assertEquals(
            $qencoderes,
            $this->Mail->encodeHeader($qencode),
            'Folded Q-encoded header value incorrect'
        );
        self::assertEquals(
            $bencodenofoldres,
            $this->Mail->encodeHeader($bencodenofold),
            'B-encoded header value incorrect'
        );
        self::assertEquals(
            $qencodenofoldres,
            $this->Mail->encodeHeader($qencodenofold),
            'Q-encoded header value incorrect'
        );
        self::assertEquals(
            $longheaderres,
            $this->Mail->encodeHeader($longheader),
            'Long header value incorrect'
        );
        self::assertEquals(
            $longutf8res,
            $this->Mail->encodeHeader($longutf8),
            'Long UTF-8 header value incorrect'
        );
        self::assertEquals(
            $noencoderes,
            $this->Mail->encodeHeader($noencode),
            'Unencoded header value incorrect'
        );
    }

    /**
     * Send an HTML message.
     */
    public function testHtml()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': HTML only';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>HTML email test</title>
    </head>
    <body>
        <h1>PHPMailer does HTML!</h1>
        <p>This is a <strong>test message</strong> written in HTML.<br>
        Go to <a href="https://github.com/PHPMailer/PHPMailer/">https://github.com/PHPMailer/PHPMailer/</a>
        for new versions of PHPMailer.</p>
        <p>Thank you!</p>
    </body>
</html>
EOT;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Send an HTML message specifying the DSN notifications we expect.
     */
    public function testDsn()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': HTML only';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>HTML email test</title>
    </head>
    <body>
        <p>PHPMailer</p>
    </body>
</html>
EOT;
        $this->buildBody();
        $this->Mail->dsn = 'SUCCESS,FAILURE';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        //Sends the same mail, but sets the DSN notification to NEVER
        $this->Mail->dsn = 'NEVER';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * createBody test of switch case
     */
    public function testCreateBody()
    {
        $PHPMailer = new PHPMailer();
        $reflection = new \ReflectionClass($PHPMailer);
        $property = $reflection->getProperty('message_type');
        $property->setAccessible(true);
        $property->setValue($PHPMailer, 'inline');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'attach');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'inline_attach');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt_inline');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt_attach');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt_inline_attach');
        self::assertIsString($PHPMailer->createBody());
    }

    /**
     * Send a message containing ISO-8859-1 text.
     */
    public function testHtmlIso8859()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': ISO-8859-1 HTML';
        $this->Mail->CharSet = PHPMailer::CHARSET_ISO88591;

        //This file is in ISO-8859-1 charset
        //Needs to be external because this file is in UTF-8
        $content = file_get_contents(realpath($this->INCLUDE_DIR . '/examples/contents.html'));
        //This is the string 'éèîüçÅñæß' in ISO-8859-1, base-64 encoded
        $check = base64_decode('6eju/OfF8ebf');
        //Make sure it really is in ISO-8859-1!
        $this->Mail->msgHTML(
            mb_convert_encoding(
                $content,
                'ISO-8859-1',
                mb_detect_encoding($content, 'UTF-8, ISO-8859-1, ISO-8859-15', true)
            ),
            realpath($this->INCLUDE_DIR . '/examples')
        );
        $this->buildBody();
        self::assertStringContainsString($check, $this->Mail->Body, 'ISO message body does not contain expected text');
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Send a message containing multilingual UTF-8 text.
     */
    public function testHtmlUtf8()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': UTF-8 HTML Пустое тело сообщения';
        $this->Mail->CharSet = 'UTF-8';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>HTML email test</title>
    </head>
    <body>
        <p>Chinese text: 郵件內容為空</p>
        <p>Russian text: Пустое тело сообщения</p>
        <p>Armenian text: Հաղորդագրությունը դատարկ է</p>
        <p>Czech text: Prázdné tělo zprávy</p>
    </body>
</html>
EOT;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Send a message containing multilingual UTF-8 text with an embedded image.
     */
    public function testUtf8WithEmbeddedImage()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': UTF-8 with embedded image';
        $this->Mail->CharSet = 'UTF-8';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>HTML email test</title>
    </head>
    <body>
        <p>Chinese text: 郵件內容為空</p>
        <p>Russian text: Пустое тело сообщения</p>
        <p>Armenian text: Հաղորդագրությունը դատարկ է</p>
        <p>Czech text: Prázdné tělo zprávy</p>
        Embedded Image: <img alt="phpmailer" src="cid:bäck">
    </body>
</html>
EOT;
        $this->Mail->addEmbeddedImage(
            realpath($this->INCLUDE_DIR . '/examples/images/phpmailer.png'),
            'bäck',
            'phpmailer.png',
            'base64',
            'image/png'
        );
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Send a message containing multilingual UTF-8 text.
     */
    public function testPlainUtf8()
    {
        $this->Mail->isHTML(false);
        $this->Mail->Subject .= ': UTF-8 plain text';
        $this->Mail->CharSet = 'UTF-8';

        $this->Mail->Body = <<<'EOT'
Chinese text: 郵件內容為空
Russian text: Пустое тело сообщения
Armenian text: Հաղորդագրությունը դատարկ է
Czech text: Prázdné tělo zprávy
EOT;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Test simple message builder and html2text converters.
     */
    public function testMsgHTML()
    {
        $message = file_get_contents(realpath($this->INCLUDE_DIR . '/examples/contentsutf8.html'));
        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->Mail->Body = '';
        $this->Mail->AltBody = '';
        //Uses internal HTML to text conversion
        $this->Mail->msgHTML($message, realpath($this->INCLUDE_DIR . '/examples'));
        $sub = $this->Mail->Subject . ': msgHTML';
        $this->Mail->Subject .= $sub;

        self::assertNotEmpty($this->Mail->Body, 'Body not set by msgHTML');
        self::assertNotEmpty($this->Mail->AltBody, 'AltBody not set by msgHTML');
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Again, using a custom HTML to text converter
        $this->Mail->AltBody = '';
        $this->Mail->msgHTML(
            $message,
            realpath($this->INCLUDE_DIR . '/examples'),
            function ($html) {
                return strtoupper(strip_tags($html));
            }
        );
        $this->Mail->Subject = $sub . ' + custom html2text';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Test that local paths without a basedir are ignored
        $this->Mail->msgHTML('<img src="/etc/hostname">test');
        self::assertStringContainsString('src="/etc/hostname"', $this->Mail->Body);
        //Test that local paths with a basedir are not ignored
        $this->Mail->msgHTML('<img src="composer.json">test', realpath($this->INCLUDE_DIR));
        self::assertStringNotContainsString('src="composer.json"', $this->Mail->Body);
        //Test that local paths with parent traversal are ignored
        $this->Mail->msgHTML('<img src="../composer.json">test', realpath($this->INCLUDE_DIR));
        self::assertStringNotContainsString('src="composer.json"', $this->Mail->Body);
        //Test that existing embedded URLs are ignored
        $this->Mail->msgHTML('<img src="cid:5d41402abc4b2a76b9719d911017c592">test');
        self::assertStringContainsString('src="cid:5d41402abc4b2a76b9719d911017c592"', $this->Mail->Body);
        //Test that absolute URLs are ignored
        $this->Mail->msgHTML('<img src="https://github.com/PHPMailer/PHPMailer/blob/master/composer.json">test');
        self::assertStringContainsString(
            'src="https://github.com/PHPMailer/PHPMailer/blob/master/composer.json"',
            $this->Mail->Body
        );
        //Test that absolute URLs with anonymous/relative protocol are ignored
        //Note that such URLs will not work in email anyway because they have no protocol to be relative to
        $this->Mail->msgHTML('<img src="//github.com/PHPMailer/PHPMailer/blob/master/composer.json">test');
        self::assertStringContainsString(
            'src="//github.com/PHPMailer/PHPMailer/blob/master/composer.json"',
            $this->Mail->Body
        );
    }

    /**
     * Simple HTML and attachment test.
     */
    public function testHTMLAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->Subject .= ': HTML + Attachment';
        $this->Mail->isHTML(true);
        $this->Mail->CharSet = 'UTF-8';

        if (
            !$this->Mail->addAttachment(
                realpath($this->INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
                'phpmailer_mini.png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        //Make sure phar paths are rejected
        self::assertFalse($this->Mail->addAttachment('phar://pharfile.php', 'pharfile.php'));
        //Make sure any path that looks URLish is rejected
        self::assertFalse($this->Mail->addAttachment('http://example.com/test.php', 'test.php'));
        self::assertFalse(
            $this->Mail->addAttachment(
                'ssh2.sftp://user:pass@attacker-controlled.example.com:22/tmp/payload.phar',
                'test.php'
            )
        );
        self::assertFalse($this->Mail->addAttachment('x-1.cd+-://example.com/test.php', 'test.php'));

        //Make sure that trying to attach a nonexistent file fails
        $filename = __FILE__ . md5(microtime()) . 'nonexistent_file.txt';
        self::assertFalse($this->Mail->addAttachment($filename));
        //Make sure that trying to attach an existing but unreadable file fails
        touch($filename);
        chmod($filename, 0200);
        self::assertFalse($this->Mail->addAttachment($filename));
        chmod($filename, 0644);
        unlink($filename);

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Attachment naming test.
     */
    public function testAttachmentNaming()
    {
        $this->Mail->Body = 'Attachments.';
        $this->Mail->Subject .= ': Attachments';
        $this->Mail->isHTML(true);
        $this->Mail->CharSet = 'UTF-8';
        $this->Mail->addAttachment(
            realpath($this->INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
            'phpmailer_mini.png";.jpg'
        );
        $this->Mail->addAttachment(
            realpath($this->INCLUDE_DIR . '/examples/images/phpmailer.png'),
            'phpmailer.png'
        );
        $this->Mail->addAttachment(
            realpath($this->INCLUDE_DIR . '/examples/images/PHPMailer card logo.png'),
            'PHPMailer card logo.png'
        );
        $this->Mail->addAttachment(
            realpath($this->INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
            'phpmailer_mini.png\\\";.jpg'
        );
        $this->buildBody();
        $this->Mail->preSend();
        $message = $this->Mail->getSentMIMEMessage();
        self::assertStringContainsString(
            'Content-Type: image/png; name="phpmailer_mini.png\";.jpg"',
            $message,
            'Name containing double quote should be escaped in Content-Type'
        );
        self::assertStringContainsString(
            'Content-Disposition: attachment; filename="phpmailer_mini.png\";.jpg"',
            $message,
            'Filename containing double quote should be escaped in Content-Disposition'
        );
        self::assertStringContainsString(
            'Content-Type: image/png; name=phpmailer.png',
            $message,
            'Name without special chars should not be quoted in Content-Type'
        );
        self::assertStringContainsString(
            'Content-Disposition: attachment; filename=phpmailer.png',
            $message,
            'Filename without special chars should not be quoted in Content-Disposition'
        );
        self::assertStringContainsString(
            'Content-Type: image/png; name="PHPMailer card logo.png"',
            $message,
            'Name with spaces should be quoted in Content-Type'
        );
        self::assertStringContainsString(
            'Content-Disposition: attachment; filename="PHPMailer card logo.png"',
            $message,
            'Filename with spaces should be quoted in Content-Disposition'
        );
    }

    /**
     * Test embedded image without a name.
     */
    public function testHTMLStringEmbedNoName()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->Subject .= ': HTML + unnamed embedded image';
        $this->Mail->isHTML(true);

        if (
            !$this->Mail->addStringEmbeddedImage(
                file_get_contents(realpath($this->INCLUDE_DIR . '/examples/images/phpmailer_mini.png')),
                hash('sha256', 'phpmailer_mini.png') . '@phpmailer.0',
                '', //Intentionally empty name
                'base64',
                '', //Intentionally empty MIME type
                'inline'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple HTML and multiple attachment test.
     */
    public function testHTMLMultiAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->Subject .= ': HTML + multiple Attachment';
        $this->Mail->isHTML(true);

        if (
            !$this->Mail->addAttachment(
                realpath($this->INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
                'phpmailer_mini.png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        if (
            !$this->Mail->addAttachment(
                realpath($this->INCLUDE_DIR . '/examples/images/phpmailer.png'),
                'phpmailer.png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * An embedded attachment test.
     */
    public function testEmbeddedImage()
    {
        $this->Mail->Body = 'Embedded Image: <img alt="phpmailer" src="' .
            'cid:my-attach">' .
            'Here is an image!';
        $this->Mail->Subject .= ': Embedded Image';
        $this->Mail->isHTML(true);

        if (
            !$this->Mail->addEmbeddedImage(
                realpath($this->INCLUDE_DIR . '/examples/images/phpmailer.png'),
                'my-attach',
                'phpmailer.png',
                'base64',
                'image/png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->clearAttachments();
        $this->Mail->msgHTML('<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>E-Mail Inline Image Test</title>
  </head>
  <body>
    <p><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="></p>
  </body>
</html>');
        $this->Mail->preSend();
        self::assertStringContainsString(
            'Content-ID: <bb229a48bee31f5d54ca12dc9bd960c6@phpmailer.0>',
            $this->Mail->getSentMIMEMessage(),
            'Embedded image header encoding incorrect.'
        );
        //For code coverage
        $this->Mail->addEmbeddedImage('thisfiledoesntexist', 'xyz'); //Non-existent file
        $this->Mail->addEmbeddedImage(__FILE__, '123'); //Missing name
    }

    /**
     * An embedded attachment test.
     */
    public function testMultiEmbeddedImage()
    {
        $this->Mail->Body = 'Embedded Image: <img alt="phpmailer" src="' .
            'cid:my-attach">' .
            'Here is an image!</a>';
        $this->Mail->Subject .= ': Embedded Image + Attachment';
        $this->Mail->isHTML(true);

        if (
            !$this->Mail->addEmbeddedImage(
                realpath($this->INCLUDE_DIR . '/examples/images/phpmailer.png'),
                'my-attach',
                'phpmailer.png',
                'base64',
                'image/png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        if (!$this->Mail->addAttachment(__FILE__, 'test.txt')) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple multipart/alternative test.
     */
    public function testAltBody()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'Here is the plain text body of this message. ' .
            'It should be quite a few lines. It should be wrapped at ' .
            '40 characters.  Make sure that it is.';
        $this->Mail->WordWrap = 40;
        $this->addNote('This is a multipart/alternative email');
        $this->Mail->Subject .= ': AltBody + Word Wrap';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple HTML and attachment test.
     */
    public function testAltBodyAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'This is the text part of the email.';
        $this->Mail->Subject .= ': AltBody + Attachment';
        $this->Mail->isHTML(true);

        if (!$this->Mail->addAttachment(__FILE__, 'test_attach.txt')) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        //Test using non-existent UNC path
        self::assertFalse($this->Mail->addAttachment('\\\\nowhere\\nothing'));

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending multiple messages with separate connections.
     */
    public function testMultipleSend()
    {
        $this->Mail->Body = 'Sending two messages without keepalive';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->Subject = $subject . ': SMTP 1';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->Subject = $subject . ': SMTP 2';
        $this->Mail->Sender = 'blah@example.com';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending using SendMail.
     */
    public function testSendmailSend()
    {
        $this->Mail->Body = 'Sending via sendmail';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->Subject = $subject . ': sendmail';
        $this->Mail->isSendmail();

        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending using Qmail.
     */
    public function testQmailSend()
    {
        //Only run if we have qmail installed
        if (file_exists('/var/qmail/bin/qmail-inject')) {
            $this->Mail->Body = 'Sending via qmail';
            $this->buildBody();
            $subject = $this->Mail->Subject;

            $this->Mail->Subject = $subject . ': qmail';
            $this->Mail->isQmail();
            self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        } else {
            self::markTestSkipped('Qmail is not installed');
        }
    }

    /**
     * Test sending using PHP mail() function.
     */
    public function testMailSend()
    {
        $sendmail = ini_get('sendmail_path');
        //No path in sendmail_path
        if (strpos($sendmail, '/') === false) {
            ini_set('sendmail_path', '/usr/sbin/sendmail -t -i ');
        }
        $this->Mail->Body = 'Sending via mail()';
        $this->buildBody();
        $this->Mail->Subject = $this->Mail->Subject . ': mail()';
        $this->Mail->clearAddresses();
        $this->Mail->clearCCs();
        $this->Mail->clearBCCs();
        $this->setAddress('testmailsend@example.com', 'totest');
        $this->setAddress('cctestmailsend@example.com', 'cctest', $sType = 'cc');
        $this->setAddress('bcctestmailsend@example.com', 'bcctest', $sType = 'bcc');
        $this->Mail->addReplyTo('replytotestmailsend@example.com', 'replytotest');
        self::assertContains('testmailsend@example.com', $this->Mail->getToAddresses()[0]);
        self::assertContains('cctestmailsend@example.com', $this->Mail->getCcAddresses()[0]);
        self::assertContains('bcctestmailsend@example.com', $this->Mail->getBccAddresses()[0]);
        self::assertContains(
            'replytotestmailsend@example.com',
            $this->Mail->getReplyToAddresses()['replytotestmailsend@example.com']
        );
        self::assertTrue($this->Mail->getAllRecipientAddresses()['testmailsend@example.com']);
        self::assertTrue($this->Mail->getAllRecipientAddresses()['cctestmailsend@example.com']);
        self::assertTrue($this->Mail->getAllRecipientAddresses()['bcctestmailsend@example.com']);

        $this->Mail->createHeader();
        $this->Mail->isMail();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Test sending an empty body.
     */
    public function testEmptyBody()
    {
        $this->buildBody();
        $this->Mail->Body = '';
        $this->Mail->Subject = $this->Mail->Subject . ': Empty Body';
        $this->Mail->isMail();
        $this->Mail->AllowEmpty = true;
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->AllowEmpty = false;
        self::assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test constructing a multipart message that contains lines that are too long for RFC compliance.
     */
    public function testLongBody()
    {
        $oklen = str_repeat(str_repeat('0', PHPMailer::MAX_LINE_LENGTH) . PHPMailer::getLE(), 2);
        //Use +2 to ensure line length is over limit - LE may only be 1 char
        $badlen = str_repeat(str_repeat('1', PHPMailer::MAX_LINE_LENGTH + 2) . PHPMailer::getLE(), 2);

        $this->Mail->Body = 'This message contains lines that are too long.' .
            PHPMailer::getLE() . $oklen . $badlen . $oklen;
        self::assertTrue(
            PHPMailer::hasLineLongerThanMax($this->Mail->Body),
            'Test content does not contain long lines!'
        );
        $this->Mail->isHTML();
        $this->buildBody();
        $this->Mail->AltBody = $this->Mail->Body;
        $this->Mail->Encoding = '8bit';
        $this->Mail->preSend();
        $message = $this->Mail->getSentMIMEMessage();
        self::assertFalse(
            PHPMailer::hasLineLongerThanMax($message),
            'Long line not corrected (Max: ' . (PHPMailer::MAX_LINE_LENGTH + strlen(PHPMailer::getLE())) . ' chars)'
        );
        self::assertStringContainsString(
            'Content-Transfer-Encoding: quoted-printable',
            $message,
            'Long line did not cause transfer encoding switch.'
        );
    }

    /**
     * Test constructing a message that does NOT contain lines that are too long for RFC compliance.
     */
    public function testShortBody()
    {
        $oklen = str_repeat(str_repeat('0', PHPMailer::MAX_LINE_LENGTH) . PHPMailer::getLE(), 10);

        $this->Mail->Body = 'This message does not contain lines that are too long.' .
            PHPMailer::getLE() . $oklen;
        self::assertFalse(
            PHPMailer::hasLineLongerThanMax($this->Mail->Body),
            'Test content contains long lines!'
        );
        $this->buildBody();
        $this->Mail->Encoding = '8bit';
        $this->Mail->preSend();
        $message = $this->Mail->getSentMIMEMessage();
        self::assertFalse(PHPMailer::hasLineLongerThanMax($message), 'Long line not corrected.');
        self::assertStringNotContainsString(
            'Content-Transfer-Encoding: quoted-printable',
            $message,
            'Short line caused transfer encoding switch.'
        );
    }

    /**
     * Test keepalive (sending multiple messages in a single connection).
     */
    public function testSmtpKeepAlive()
    {
        $this->Mail->Body = 'SMTP keep-alive test.';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->SMTPKeepAlive = true;
        $this->Mail->Subject = $subject . ': SMTP keep-alive 1';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->Subject = $subject . ': SMTP keep-alive 2';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->smtpClose();
    }

    /**
     * Test this denial of service attack.
     *
     * @see http://www.cybsec.com/vuln/PHPMailer-DOS.pdf
     */
    public function testDenialOfServiceAttack()
    {
        $this->Mail->Body = 'This should no longer cause a denial of service.';
        $this->buildBody();

        $this->Mail->Subject = substr(str_repeat('0123456789', 100), 0, 998);
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Tests this denial of service attack.
     *
     * According to the ticket, this should get stuck in a loop, though I can't make it happen.
     * @see https://sourceforge.net/p/phpmailer/bugs/383/
     *
     * @doesNotPerformAssertions
     */
    public function testDenialOfServiceAttack2()
    {
        //Encoding name longer than 68 chars
        $this->Mail->Encoding = '1234567890123456789012345678901234567890123456789012345678901234567890';
        //Call wrapText with a zero length value
        $this->Mail->wrapText(str_repeat('This should no longer cause a denial of service. ', 30), 0);
    }

    /**
     * Test error handling.
     */
    public function testError()
    {
        $this->Mail->Subject .= ': Error handling test - this should be sent ok';
        $this->buildBody();
        $this->Mail->clearAllRecipients(); //No addresses should cause an error
        self::assertTrue($this->Mail->isError() == false, 'Error found');
        self::assertTrue($this->Mail->send() == false, 'send succeeded');
        self::assertTrue($this->Mail->isError(), 'No error found');
        self::assertEquals('You must provide at least one recipient email address.', $this->Mail->ErrorInfo);
        $this->Mail->addAddress($_REQUEST['mail_to']);
        self::assertTrue($this->Mail->send(), 'send failed');
    }

    /**
     * Test addressing.
     */
    public function testAddressing()
    {
        self::assertFalse($this->Mail->addAddress(''), 'Empty address accepted');
        self::assertFalse($this->Mail->addAddress('', 'Nobody'), 'Empty address with name accepted');
        self::assertFalse($this->Mail->addAddress('a@example..com'), 'Invalid address accepted');
        self::assertTrue($this->Mail->addAddress('a@example.com'), 'Addressing failed');
        self::assertFalse($this->Mail->addAddress('a@example.com'), 'Duplicate addressing failed');
        self::assertTrue($this->Mail->addCC('b@example.com'), 'CC addressing failed');
        self::assertFalse($this->Mail->addCC('b@example.com'), 'CC duplicate addressing failed');
        self::assertFalse($this->Mail->addCC('a@example.com'), 'CC duplicate addressing failed (2)');
        self::assertTrue($this->Mail->addBCC('c@example.com'), 'BCC addressing failed');
        self::assertFalse($this->Mail->addBCC('c@example.com'), 'BCC duplicate addressing failed');
        self::assertFalse($this->Mail->addBCC('a@example.com'), 'BCC duplicate addressing failed (2)');
        self::assertTrue($this->Mail->addReplyTo('a@example.com'), 'Replyto Addressing failed');
        self::assertFalse($this->Mail->addReplyTo('a@example..com'), 'Invalid Replyto address accepted');
        self::assertTrue($this->Mail->setFrom('a@example.com', 'some name'), 'setFrom failed');
        self::assertFalse($this->Mail->setFrom('a@example.com.', 'some name'), 'setFrom accepted invalid address');
        $this->Mail->Sender = '';
        $this->Mail->setFrom('a@example.com', 'some name', true);
        self::assertEquals($this->Mail->Sender, 'a@example.com', 'setFrom failed to set sender');
        $this->Mail->Sender = '';
        $this->Mail->setFrom('a@example.com', 'some name', false);
        self::assertEquals($this->Mail->Sender, '', 'setFrom should not have set sender');
        $this->Mail->clearCCs();
        $this->Mail->clearBCCs();
        $this->Mail->clearReplyTos();
    }

    /**
     * Test addressing.
     */
    public function testAddressing2()
    {
        $this->buildBody();
        $this->Mail->setFrom('bob@example.com', '"Bob\'s Burgers" (Bob\'s "Burgers")', true);
        $this->Mail->isSMTP();
        $this->Mail->Subject .= ': quotes in from name';
        self::assertTrue($this->Mail->send(), 'send failed');
    }

    /**
     * Test RFC822 address splitting.
     */
    public function testAddressSplitting()
    {
        //Test built-in address parser
        self::assertCount(
            2,
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com>, Jill User <jill@example.net>'
            ),
            'Failed to recognise address list (IMAP parser)'
        );
        self::assertEquals(
            [
                ['name' => 'Joe User', 'address' => 'joe@example.com'],
                ['name' => 'Jill User', 'address' => 'jill@example.net'],
                ['name' => '', 'address' => 'frank@example.com'],
            ],
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com>,'
                . 'Jill User <jill@example.net>,'
                . 'frank@example.com,'
            ),
            'Parsed addresses'
        );
        //Test simple address parser
        self::assertCount(
            2,
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com>, Jill User <jill@example.net>',
                false
            ),
            'Failed to recognise address list'
        );
        //Test single address
        self::assertNotEmpty(
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com>',
                false
            ),
            'Failed to recognise single address'
        );
        //Test quoted name IMAP
        self::assertNotEmpty(
            PHPMailer::parseAddresses(
                'Tim "The Book" O\'Reilly <foo@example.com>'
            ),
            'Failed to recognise quoted name (IMAP)'
        );
        //Test quoted name
        self::assertNotEmpty(
            PHPMailer::parseAddresses(
                'Tim "The Book" O\'Reilly <foo@example.com>',
                false
            ),
            'Failed to recognise quoted name'
        );
        //Test single address IMAP
        self::assertNotEmpty(
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com>'
            ),
            'Failed to recognise single address (IMAP)'
        );
        //Test unnamed address
        self::assertNotEmpty(
            PHPMailer::parseAddresses(
                'joe@example.com',
                false
            ),
            'Failed to recognise unnamed address'
        );
        //Test unnamed address IMAP
        self::assertNotEmpty(
            PHPMailer::parseAddresses(
                'joe@example.com'
            ),
            'Failed to recognise unnamed address (IMAP)'
        );
        //Test invalid addresses
        self::assertEmpty(
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com.>, Jill User <jill.@example.net>'
            ),
            'Failed to recognise invalid addresses (IMAP)'
        );
        //Test invalid addresses
        self::assertEmpty(
            PHPMailer::parseAddresses(
                'Joe User <joe@example.com.>, Jill User <jill.@example.net>',
                false
            ),
            'Failed to recognise invalid addresses'
        );
    }

    /**
     * Test address escaping.
     */
    public function testAddressEscaping()
    {
        $this->Mail->Subject .= ': Address escaping';
        $this->Mail->clearAddresses();
        $this->Mail->addAddress('foo@example.com', 'Tim "The Book" O\'Reilly');
        $this->Mail->Body = 'Test correct escaping of quotes in addresses.';
        $this->buildBody();
        $this->Mail->preSend();
        $b = $this->Mail->getSentMIMEMessage();
        self::assertStringContainsString('To: "Tim \"The Book\" O\'Reilly" <foo@example.com>', $b);

        $this->Mail->Subject .= ': Address escaping invalid';
        $this->Mail->clearAddresses();
        $this->Mail->addAddress('foo@example.com', 'Tim "The Book" O\'Reilly');
        $this->Mail->addAddress('invalidaddressexample.com', 'invalidaddress');
        $this->Mail->Body = 'invalid address';
        $this->buildBody();
        $this->Mail->preSend();
        self::assertEquals('Invalid address:  (to): invalidaddressexample.com', $this->Mail->ErrorInfo);

        $this->Mail->addAttachment(
            realpath($this->INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
            'phpmailer_mini.png'
        );
        self::assertTrue($this->Mail->attachmentExists());
    }

    /**
     * Test MIME structure assembly.
     */
    public function testMIMEStructure()
    {
        $this->Mail->Subject .= ': MIME structure';
        $this->Mail->Body = '<h3>MIME structure test.</h3>';
        $this->Mail->AltBody = 'MIME structure test.';
        $this->buildBody();
        $this->Mail->preSend();
        self::assertMatchesRegularExpression(
            "/Content-Transfer-Encoding: 8bit\r\n\r\n" .
            'This is a multi-part message in MIME format./',
            $this->Mail->getSentMIMEMessage(),
            'MIME structure broken'
        );
    }

    /**
     * Test BCC-only addressing.
     */
    public function testBCCAddressing()
    {
        $this->Mail->isSMTP();
        $this->Mail->Subject .= ': BCC-only addressing';
        $this->buildBody();
        $this->Mail->clearAllRecipients();
        $this->Mail->addAddress('foo@example.com', 'Foo');
        $this->Mail->preSend();
        $b = $this->Mail->getSentMIMEMessage();
        self::assertTrue($this->Mail->addBCC('a@example.com'), 'BCC addressing failed');
        self::assertStringContainsString('To: Foo <foo@example.com>', $b);
        self::assertTrue($this->Mail->send(), 'send failed');
    }

    /**
     * Encoding and charset tests.
     */
    public function testEncodings()
    {
        $this->Mail->CharSet = PHPMailer::CHARSET_ISO88591;
        self::assertEquals(
            '=A1Hola!_Se=F1or!',
            $this->Mail->encodeQ("\xa1Hola! Se\xf1or!", 'text'),
            'Q Encoding (text) failed'
        );
        self::assertEquals(
            '=A1Hola!_Se=F1or!',
            $this->Mail->encodeQ("\xa1Hola! Se\xf1or!", 'comment'),
            'Q Encoding (comment) failed'
        );
        self::assertEquals(
            '=A1Hola!_Se=F1or!',
            $this->Mail->encodeQ("\xa1Hola! Se\xf1or!", 'phrase'),
            'Q Encoding (phrase) failed'
        );
        $this->Mail->CharSet = 'UTF-8';
        self::assertEquals(
            '=C2=A1Hola!_Se=C3=B1or!',
            $this->Mail->encodeQ("\xc2\xa1Hola! Se\xc3\xb1or!", 'text'),
            'Q Encoding (text) failed'
        );
        //Strings containing '=' are a special case
        self::assertEquals(
            'Nov=C3=A1=3D',
            $this->Mail->encodeQ("Nov\xc3\xa1=", 'text'),
            'Q Encoding (text) failed 2'
        );

        self::assertEquals(
            'hello',
            $this->Mail->encodeString('hello', 'binary'),
            'Binary encoding changed input'
        );
        $this->Mail->ErrorInfo = '';
        $this->Mail->encodeString('hello', 'asdfghjkl');
        self::assertNotEmpty($this->Mail->ErrorInfo, 'Invalid encoding not detected');
        self::assertMatchesRegularExpression(
            '/' . base64_encode('hello') . '/',
            $this->Mail->encodeString('hello')
        );
    }

    /**
     * Expect exceptions on bad encoding
     */
    public function testAddAttachmentEncodingException()
    {
        $this->expectException(Exception::class);

        $mail = new PHPMailer(true);
        $mail->addAttachment(__FILE__, 'test.txt', 'invalidencoding');
    }

    /**
     * Expect exceptions on sending after deleting a previously successfully attached file
     */
    public function testDeletedAttachmentException()
    {
        $this->expectException(Exception::class);

        $filename = __FILE__ . md5(microtime()) . 'test.txt';
        touch($filename);
        $this->Mail = new PHPMailer(true);
        $this->Mail->addAttachment($filename);
        unlink($filename);
        $this->Mail->send();
    }

    /**
     * Expect error on sending after deleting a previously successfully attached file
     */
    public function testDeletedAttachmentError()
    {
        $filename = __FILE__ . md5(microtime()) . 'test.txt';
        touch($filename);
        $this->Mail = new PHPMailer();
        $this->Mail->addAttachment($filename);
        unlink($filename);
        self::assertFalse($this->Mail->send());
    }

    /**
     * Expect exceptions on bad encoding
     */
    public function testStringAttachmentEncodingException()
    {
        $this->expectException(Exception::class);

        $mail = new PHPMailer(true);
        $mail->addStringAttachment('hello', 'test.txt', 'invalidencoding');
    }

    /**
     * Expect exceptions on bad encoding
     */
    public function testEmbeddedImageEncodingException()
    {
        $this->expectException(Exception::class);

        $mail = new PHPMailer(true);
        $mail->addEmbeddedImage(__FILE__, 'cid', 'test.png', 'invalidencoding');
    }

    /**
     * Expect exceptions on bad encoding
     */
    public function testStringEmbeddedImageEncodingException()
    {
        $this->expectException(Exception::class);

        $mail = new PHPMailer(true);
        $mail->addStringEmbeddedImage('hello', 'cid', 'test.png', 'invalidencoding');
    }

    /**
     * Test base-64 encoding.
     */
    public function testBase64()
    {
        $this->Mail->Subject .= ': Base-64 encoding';
        $this->Mail->Encoding = 'base64';
        $this->buildBody();
        self::assertTrue($this->Mail->send(), 'Base64 encoding failed');
    }

    /**
     * S/MIME Signing tests (self-signed).
     *
     * @requires extension openssl
     */
    public function testSigning()
    {
        $this->Mail->Subject .= ': S/MIME signing';
        $this->Mail->Body = 'This message is S/MIME signed.';
        $this->buildBody();

        $dn = [
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer',
            'commonName' => 'PHPMailer Test',
            'emailAddress' => 'phpmailer@example.com',
        ];
        $keyconfig = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $password = 'password';
        $certfile = 'certfile.pem';
        $keyfile = 'keyfile.pem';

        //Make a new key pair
        $pk = openssl_pkey_new($keyconfig);
        //Create a certificate signing request
        $csr = openssl_csr_new($dn, $pk);
        //Create a self-signed cert
        $cert = openssl_csr_sign($csr, null, $pk, 1);
        //Save the cert
        openssl_x509_export($cert, $certout);
        file_put_contents($certfile, $certout);
        //Save the key
        openssl_pkey_export($pk, $pkeyout, $password);
        file_put_contents($keyfile, $pkeyout);

        $this->Mail->sign(
            $certfile,
            $keyfile,
            $password
        );
        self::assertTrue($this->Mail->send(), 'S/MIME signing failed');

        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
        unlink($certfile);
        unlink($keyfile);
    }

    /**
     * S/MIME Signing tests using a CA chain cert.
     * To test that a generated message is signed correctly, save the message in a file called `signed.eml`
     * and use openssl along with the certs generated by this script:
     * `openssl smime -verify -in signed.eml -signer certfile.pem -CAfile cacertfile.pem`.
     *
     * @requires extension openssl
     */
    public function testSigningWithCA()
    {
        $this->Mail->Subject .= ': S/MIME signing with CA';
        $this->Mail->Body = 'This message is S/MIME signed with an extra CA cert.';
        $this->buildBody();

        $certprops = [
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer',
            'commonName' => 'PHPMailer Test',
            'emailAddress' => 'phpmailer@example.com',
        ];
        $cacertprops = [
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer CA',
            'commonName' => 'PHPMailer Test CA',
            'emailAddress' => 'phpmailer@example.com',
        ];
        $keyconfig = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $password = 'password';
        $cacertfile = 'cacertfile.pem';
        $cakeyfile = 'cakeyfile.pem';
        $certfile = 'certfile.pem';
        $keyfile = 'keyfile.pem';

        //Create a CA cert
        //Make a new key pair
        $capk = openssl_pkey_new($keyconfig);
        //Create a certificate signing request
        $csr = openssl_csr_new($cacertprops, $capk);
        //Create a self-signed cert
        $cert = openssl_csr_sign($csr, null, $capk, 1);
        //Save the CA cert
        openssl_x509_export($cert, $certout);
        file_put_contents($cacertfile, $certout);
        //Save the CA key
        openssl_pkey_export($capk, $pkeyout, $password);
        file_put_contents($cakeyfile, $pkeyout);

        //Create a cert signed by our CA
        //Make a new key pair
        $pk = openssl_pkey_new($keyconfig);
        //Create a certificate signing request
        $csr = openssl_csr_new($certprops, $pk);
        //Create a self-signed cert
        $cacert = file_get_contents($cacertfile);
        $cert = openssl_csr_sign($csr, $cacert, $capk, 1);
        //Save the cert
        openssl_x509_export($cert, $certout);
        file_put_contents($certfile, $certout);
        //Save the key
        openssl_pkey_export($pk, $pkeyout, $password);
        file_put_contents($keyfile, $pkeyout);

        $this->Mail->sign(
            $certfile,
            $keyfile,
            $password,
            $cacertfile
        );
        self::assertTrue($this->Mail->send(), 'S/MIME signing with CA failed');
        unlink($cacertfile);
        unlink($cakeyfile);
        unlink($certfile);
        unlink($keyfile);
    }

    /**
     * DKIM body canonicalization tests.
     *
     * @see https://tools.ietf.org/html/rfc6376#section-3.4.4
     */
    public function testDKIMBodyCanonicalization()
    {
        //Example from https://tools.ietf.org/html/rfc6376#section-3.4.5
        $prebody = " C \r\nD \t E\r\n\r\n\r\n";
        $postbody = " C \r\nD \t E\r\n";
        self::assertEquals($this->Mail->DKIM_BodyC(''), "\r\n", 'DKIM empty body canonicalization incorrect');
        self::assertEquals(
            'frcCV1k9oG9oKj3dpUqdJg1PxRT2RSN/XKdLCPjaYaY=',
            base64_encode(hash('sha256', $this->Mail->DKIM_BodyC(''), true)),
            'DKIM canonicalized empty body hash mismatch'
        );
        self::assertEquals($this->Mail->DKIM_BodyC($prebody), $postbody, 'DKIM body canonicalization incorrect');
    }

    /**
     * DKIM header canonicalization tests.
     *
     * @see https://tools.ietf.org/html/rfc6376#section-3.4.2
     */
    public function testDKIMHeaderCanonicalization()
    {
        //Example from https://tools.ietf.org/html/rfc6376#section-3.4.5
        $preheaders = "A: X\r\nB : Y\t\r\n\tZ  \r\n";
        $postheaders = "a:X\r\nb:Y Z\r\n";
        self::assertEquals(
            $postheaders,
            $this->Mail->DKIM_HeaderC($preheaders),
            'DKIM header canonicalization incorrect'
        );
        //Check that long folded lines with runs of spaces are canonicalized properly
        $preheaders = 'Long-Header-1: <https://example.com/somescript.php?' .
            "id=1234567890&name=Abcdefghijklmnopquestuvwxyz&hash=\r\n abc1234\r\n" .
            "Long-Header-2: This  is  a  long  header  value  that  contains  runs  of  spaces and trailing    \r\n" .
            ' and   is   folded   onto   2   lines';
        $postheaders = 'long-header-1:<https://example.com/somescript.php?id=1234567890&' .
            "name=Abcdefghijklmnopquestuvwxyz&hash= abc1234\r\nlong-header-2:This is a long" .
            ' header value that contains runs of spaces and trailing and is folded onto 2 lines';
        self::assertEquals(
            $postheaders,
            $this->Mail->DKIM_HeaderC($preheaders),
            'DKIM header canonicalization of long lines incorrect'
        );
    }

    /**
     * DKIM copied header fields tests.
     *
     * @group dkim
     *
     * @see https://tools.ietf.org/html/rfc6376#section-3.5
     */
    public function testDKIMOptionalHeaderFieldsCopy()
    {
        $privatekeyfile = 'dkim_private.pem';
        $pk = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        openssl_pkey_export_to_file($pk, $privatekeyfile);
        $this->Mail->DKIM_private = 'dkim_private.pem';

        //Example from https://tools.ietf.org/html/rfc6376#section-3.5
        $from = 'from@example.com';
        $to = 'to@example.com';
        $date = 'date';
        $subject = 'example';

        $headerLines = "From:$from\r\nTo:$to\r\nDate:$date\r\n";
        $copyHeaderFields = " z=From:$from\r\n |To:$to\r\n |Date:$date\r\n |Subject:$subject;\r\n";

        $this->Mail->DKIM_copyHeaderFields = true;
        self::assertStringContainsString(
            $copyHeaderFields,
            $this->Mail->DKIM_Add($headerLines, $subject, ''),
            'DKIM header with copied header fields incorrect'
        );

        $this->Mail->DKIM_copyHeaderFields = false;
        self::assertStringNotContainsString(
            $copyHeaderFields,
            $this->Mail->DKIM_Add($headerLines, $subject, ''),
            'DKIM header without copied header fields incorrect'
        );

        unlink($privatekeyfile);
    }

    /**
     * DKIM signing extra headers tests.
     *
     * @group dkim
     */
    public function testDKIMExtraHeaders()
    {
        $privatekeyfile = 'dkim_private.pem';
        $pk = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        openssl_pkey_export_to_file($pk, $privatekeyfile);
        $this->Mail->DKIM_private = 'dkim_private.pem';

        //Example from https://tools.ietf.org/html/rfc6376#section-3.5
        $from = 'from@example.com';
        $to = 'to@example.com';
        $date = 'date';
        $subject = 'example';
        $anyHeader = 'foo';
        $unsubscribeUrl = '<https://www.example.com/unsubscribe/?newsletterId=anytoken&amp;actionToken=anyToken' .
                            '&otherParam=otherValue&anotherParam=anotherVeryVeryVeryLongValue>';

        $this->Mail->addCustomHeader('X-AnyHeader', $anyHeader);
        $this->Mail->addCustomHeader('Baz', 'bar');
        $this->Mail->addCustomHeader('List-Unsubscribe', $unsubscribeUrl);

        $this->Mail->DKIM_extraHeaders = ['Baz', 'List-Unsubscribe'];

        $headerLines = "From:$from\r\nTo:$to\r\nDate:$date\r\n";
        $headerLines .= "X-AnyHeader:$anyHeader\r\nBaz:bar\r\n";
        $headerLines .= 'List-Unsubscribe:' . $this->Mail->encodeHeader($unsubscribeUrl) . "\r\n";

        $headerFields = 'h=From:To:Date:Baz:List-Unsubscribe:Subject';

        $result = $this->Mail->DKIM_Add($headerLines, $subject, '');

        self::assertStringContainsString($headerFields, $result, 'DKIM header with extra headers incorrect');

        unlink($privatekeyfile);
    }

    /**
     * DKIM Signing tests.
     *
     * @requires extension openssl
     */
    public function testDKIM()
    {
        $this->Mail->Subject .= ': DKIM signing';
        $this->Mail->Body = 'This message is DKIM signed.';
        $this->buildBody();
        $privatekeyfile = 'dkim_private.pem';
        //Make a new key pair
        //(2048 bits is the recommended minimum key length -
        //gmail won't accept less than 1024 bits)
        $pk = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        openssl_pkey_export_to_file($pk, $privatekeyfile);
        $this->Mail->DKIM_domain = 'example.com';
        $this->Mail->DKIM_private = $privatekeyfile;
        $this->Mail->DKIM_selector = 'phpmailer';
        $this->Mail->DKIM_passphrase = ''; //key is not encrypted
        self::assertTrue($this->Mail->send(), 'DKIM signed mail failed');
        $this->Mail->isMail();
        self::assertTrue($this->Mail->send(), 'DKIM signed mail via mail() failed');
        unlink($privatekeyfile);
    }

    /**
     * Test line break reformatting.
     */
    public function testLineBreaks()
    {
        //May have been altered by earlier tests, can interfere with line break format
        $this->Mail->isSMTP();
        $this->Mail->preSend();
        $unixsrc = "hello\nWorld\nAgain\n";
        $macsrc = "hello\rWorld\rAgain\r";
        $windowssrc = "hello\r\nWorld\r\nAgain\r\n";
        $mixedsrc = "hello\nWorld\rAgain\r\n";
        $target = "hello\r\nWorld\r\nAgain\r\n";
        self::assertEquals($target, PHPMailer::normalizeBreaks($unixsrc), 'UNIX break reformatting failed');
        self::assertEquals($target, PHPMailer::normalizeBreaks($macsrc), 'Mac break reformatting failed');
        self::assertEquals($target, PHPMailer::normalizeBreaks($windowssrc), 'Windows break reformatting failed');
        self::assertEquals($target, PHPMailer::normalizeBreaks($mixedsrc), 'Mixed break reformatting failed');

        //To see accurate results when using postfix, set `sendmail_fix_line_endings = never` in main.cf
        $this->Mail->Subject = 'PHPMailer DOS line breaks';
        $this->Mail->Body = "This message\r\ncontains\r\nDOS-format\r\nCRLF line breaks.";
        self::assertTrue($this->Mail->send());

        $this->Mail->Subject = 'PHPMailer UNIX line breaks';
        $this->Mail->Body = "This message\ncontains\nUNIX-format\nLF line breaks.";
        self::assertTrue($this->Mail->send());

        $this->Mail->Encoding = 'quoted-printable';
        $this->Mail->Subject = 'PHPMailer DOS line breaks, QP';
        $this->Mail->Body = "This message\r\ncontains\r\nDOS-format\r\nCRLF line breaks.";
        self::assertTrue($this->Mail->send());

        $this->Mail->Subject = 'PHPMailer UNIX line breaks, QP';
        $this->Mail->Body = "This message\ncontains\nUNIX-format\nLF line breaks.";
        self::assertTrue($this->Mail->send());
    }

    /**
     * Test line length detection.
     */
    public function testLineLength()
    {
        //May have been altered by earlier tests, can interfere with line break format
        $this->Mail->isSMTP();
        $this->Mail->preSend();
        $oklen = str_repeat(str_repeat('0', PHPMailer::MAX_LINE_LENGTH) . "\r\n", 2);
        $badlen = str_repeat(str_repeat('1', PHPMailer::MAX_LINE_LENGTH + 1) . "\r\n", 2);
        self::assertTrue(PHPMailer::hasLineLongerThanMax($badlen), 'Long line not detected (only)');
        self::assertTrue(PHPMailer::hasLineLongerThanMax($oklen . $badlen), 'Long line not detected (first)');
        self::assertTrue(PHPMailer::hasLineLongerThanMax($badlen . $oklen), 'Long line not detected (last)');
        self::assertTrue(
            PHPMailer::hasLineLongerThanMax($oklen . $badlen . $oklen),
            'Long line not detected (middle)'
        );
        self::assertFalse(PHPMailer::hasLineLongerThanMax($oklen), 'Long line false positive');
        $this->Mail->isHTML(false);
        $this->Mail->Subject .= ': Line length test';
        $this->Mail->CharSet = 'UTF-8';
        $this->Mail->Encoding = '8bit';
        $this->Mail->Body = $oklen . $badlen . $oklen . $badlen;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        self::assertEquals('quoted-printable', $this->Mail->Encoding, 'Long line did not override transfer encoding');
    }

    /**
     * Test setting and retrieving message ID.
     */
    public function testMessageID()
    {
        $this->Mail->Body = 'Test message ID.';
        $id = hash('sha256', 12345);
        $this->Mail->MessageID = $id;
        $this->buildBody();
        $this->Mail->preSend();
        $lastid = $this->Mail->getLastMessageID();
        self::assertNotEquals($lastid, $id, 'Invalid Message ID allowed');
        $id = '<' . hash('sha256', 12345) . '@example.com>';
        $this->Mail->MessageID = $id;
        $this->buildBody();
        $this->Mail->preSend();
        $lastid = $this->Mail->getLastMessageID();
        self::assertEquals($lastid, $id, 'Custom Message ID not used');
        $this->Mail->MessageID = '';
        $this->buildBody();
        $this->Mail->preSend();
        $lastid = $this->Mail->getLastMessageID();
        self::assertMatchesRegularExpression('/^<.*@.*>$/', $lastid, 'Invalid default Message ID');
    }

    /**
     * Check whether setting a bad custom header throws exceptions.
     *
     * @throws Exception
     */
    public function testHeaderException()
    {
        $this->expectException(Exception::class);

        $mail = new PHPMailer(true);
        $mail->addCustomHeader('SomeHeader', "Some\n Value");
    }

    /**
     * Miscellaneous calls to improve test coverage and some small tests.
     */
    public function testMiscellaneous()
    {
        self::assertEquals('application/pdf', PHPMailer::_mime_types('pdf'), 'MIME TYPE lookup failed');
        $this->Mail->clearAttachments();
        $this->Mail->isHTML(false);
        $this->Mail->isSMTP();
        $this->Mail->isMail();
        $this->Mail->isSendmail();
        $this->Mail->isQmail();
        $this->Mail->setLanguage('fr');
        $this->Mail->Sender = '';
        $this->Mail->createHeader();
        self::assertFalse($this->Mail->set('x', 'y'), 'Invalid property set succeeded');
        self::assertTrue($this->Mail->set('Timeout', 11), 'Valid property set failed');
        self::assertTrue($this->Mail->set('AllowEmpty', null), 'Null property set failed');
        self::assertTrue($this->Mail->set('AllowEmpty', false), 'Valid property set of null property failed');
        //Test pathinfo
        $a = '/mnt/files/飛兒樂 團光茫.mp3';
        $q = PHPMailer::mb_pathinfo($a);
        self::assertEquals($q['dirname'], '/mnt/files', 'UNIX dirname not matched');
        self::assertEquals($q['basename'], '飛兒樂 團光茫.mp3', 'UNIX basename not matched');
        self::assertEquals($q['extension'], 'mp3', 'UNIX extension not matched');
        self::assertEquals($q['filename'], '飛兒樂 團光茫', 'UNIX filename not matched');
        self::assertEquals(
            PHPMailer::mb_pathinfo($a, PATHINFO_DIRNAME),
            '/mnt/files',
            'Dirname path element not matched'
        );
        self::assertEquals(
            PHPMailer::mb_pathinfo($a, PATHINFO_BASENAME),
            '飛兒樂 團光茫.mp3',
            'Basename path element not matched'
        );
        self::assertEquals(PHPMailer::mb_pathinfo($a, 'filename'), '飛兒樂 團光茫', 'Filename path element not matched');
        $a = 'c:\mnt\files\飛兒樂 團光茫.mp3';
        $q = PHPMailer::mb_pathinfo($a);
        self::assertEquals($q['dirname'], 'c:\mnt\files', 'Windows dirname not matched');
        self::assertEquals($q['basename'], '飛兒樂 團光茫.mp3', 'Windows basename not matched');
        self::assertEquals($q['extension'], 'mp3', 'Windows extension not matched');
        self::assertEquals($q['filename'], '飛兒樂 團光茫', 'Windows filename not matched');

        self::assertEquals(
            PHPMailer::filenameToType('abc.jpg?xyz=1'),
            'image/jpeg',
            'Query string not ignored in filename'
        );
        self::assertEquals(
            PHPMailer::filenameToType('abc.xyzpdq'),
            'application/octet-stream',
            'Default MIME type not applied to unknown extension'
        );

        //Line break normalization
        $eol = PHPMailer::getLE();
        $b1 = "1\r2\r3\r";
        $b2 = "1\n2\n3\n";
        $b3 = "1\r\n2\r3\n";
        $t1 = "1{$eol}2{$eol}3{$eol}";
        self::assertEquals(PHPMailer::normalizeBreaks($b1), $t1, 'Failed to normalize line breaks (1)');
        self::assertEquals(PHPMailer::normalizeBreaks($b2), $t1, 'Failed to normalize line breaks (2)');
        self::assertEquals(PHPMailer::normalizeBreaks($b3), $t1, 'Failed to normalize line breaks (3)');
    }

    public function testBadSMTP()
    {
        $this->Mail->smtpConnect();
        $smtp = $this->Mail->getSMTPInstance();
        self::assertFalse($smtp->mail("somewhere\nbad"), 'Bad SMTP command containing breaks accepted');
    }

    public function testHostValidation()
    {
        $good = [
            'localhost',
            'example.com',
            'smtp.gmail.com',
            '127.0.0.1',
            trim(str_repeat('a0123456789.', 21), '.'),
            '[::1]',
            '[0:1234:dc0:41:216:3eff:fe67:3e01]',
        ];
        $bad = [
            null,
            123,
            1.5,
            new \stdClass(),
            [],
            '',
            '999.0.0.0',
            '[1234]',
            '[1234:::1]',
            trim(str_repeat('a0123456789.', 22), '.'),
            '0:1234:dc0:41:216:3eff:fe67:3e01',
            '[012q:1234:dc0:41:216:3eff:fe67:3e01]',
            '[[::1]]',
        ];
        foreach ($good as $h) {
            self::assertTrue(PHPMailer::isValidHost($h), 'Good hostname denied: ' . $h);
        }
        foreach ($bad as $h) {
            self::assertFalse(PHPMailer::isValidHost($h), 'Bad hostname accepted: ' . var_export($h, true));
        }
    }

    /**
     * Tests the Custom header getter.
     */
    public function testCustomHeaderGetter()
    {
        $this->Mail->addCustomHeader('foo', 'bar');
        self::assertEquals([['foo', 'bar']], $this->Mail->getCustomHeaders());

        $this->Mail->addCustomHeader('foo', 'baz');
        self::assertEquals(
            [
                ['foo', 'bar'],
                ['foo', 'baz'],
            ],
            $this->Mail->getCustomHeaders()
        );

        $this->Mail->clearCustomHeaders();
        self::assertEmpty($this->Mail->getCustomHeaders());

        $this->Mail->addCustomHeader('yux');
        self::assertEquals([['yux', '']], $this->Mail->getCustomHeaders());

        $this->Mail->addCustomHeader('Content-Type: application/json');
        self::assertEquals(
            [
                ['yux', ''],
                ['Content-Type', 'application/json'],
            ],
            $this->Mail->getCustomHeaders()
        );
        $this->Mail->clearCustomHeaders();
        $this->Mail->addCustomHeader('SomeHeader: Some Value');
        $headers = $this->Mail->getCustomHeaders();
        self::assertEquals($headers[0], ['SomeHeader', 'Some Value']);
        $this->Mail->clearCustomHeaders();
        $this->Mail->addCustomHeader('SomeHeader', 'Some Value');
        $headers = $this->Mail->getCustomHeaders();
        self::assertEquals($headers[0], ['SomeHeader', 'Some Value']);
        $this->Mail->clearCustomHeaders();
        self::assertFalse($this->Mail->addCustomHeader('SomeHeader', "Some\n Value"));
        self::assertFalse($this->Mail->addCustomHeader("Some\nHeader", 'Some Value'));
    }

    /**
     * Tests setting and retrieving ConfirmReadingTo address, also known as "read receipt" address.
     */
    public function testConfirmReadingTo()
    {
        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->buildBody();

        $this->Mail->ConfirmReadingTo = 'test@example..com';  //Invalid address
        self::assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->ConfirmReadingTo = ' test@example.com';  //Extra space to trim
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        self::assertEquals(
            'test@example.com',
            $this->Mail->ConfirmReadingTo,
            'Unexpected read receipt address'
        );

        $letter = html_entity_decode('&ccedil;', ENT_COMPAT, PHPMailer::CHARSET_UTF8);
        $this->Mail->ConfirmReadingTo = 'test@fran' . $letter . 'ois.ch';  //Address with IDN
        if (PHPMailer::idnSupported()) {
            self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
            self::assertEquals(
                'test@xn--franois-xxa.ch',
                $this->Mail->ConfirmReadingTo,
                'IDN address not converted to punycode'
            );
        } else {
            self::assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);
        }
    }

    /**
     * Tests CharSet and Unicode -> ASCII conversions for addresses with IDN.
     */
    public function testConvertEncoding()
    {
        if (!PHPMailer::idnSupported()) {
            self::markTestSkipped('intl and/or mbstring extensions are not available');
        }

        $this->Mail->clearAllRecipients();
        $this->Mail->clearReplyTos();

        //This file is UTF-8 encoded. Create a domain encoded in "iso-8859-1".
        $letter = html_entity_decode('&ccedil;', ENT_COMPAT, PHPMailer::CHARSET_ISO88591);
        $domain = '@' . 'fran' . $letter . 'ois.ch';
        $this->Mail->addAddress('test' . $domain);
        $this->Mail->addCC('test+cc' . $domain);
        $this->Mail->addBCC('test+bcc' . $domain);
        $this->Mail->addReplyTo('test+replyto' . $domain);

        //Queued addresses are not returned by get*Addresses() before send() call.
        self::assertEmpty($this->Mail->getToAddresses(), 'Bad "to" recipients');
        self::assertEmpty($this->Mail->getCcAddresses(), 'Bad "cc" recipients');
        self::assertEmpty($this->Mail->getBccAddresses(), 'Bad "bcc" recipients');
        self::assertEmpty($this->Mail->getReplyToAddresses(), 'Bad "reply-to" recipients');

        //Clear queued BCC recipient.
        $this->Mail->clearBCCs();

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Addresses with IDN are returned by get*Addresses() after send() call.
        $domain = $this->Mail->punyencodeAddress($domain);
        self::assertEquals(
            [['test' . $domain, '']],
            $this->Mail->getToAddresses(),
            'Bad "to" recipients'
        );
        self::assertEquals(
            [['test+cc' . $domain, '']],
            $this->Mail->getCcAddresses(),
            'Bad "cc" recipients'
        );
        self::assertEmpty($this->Mail->getBccAddresses(), 'Bad "bcc" recipients');
        self::assertEquals(
            ['test+replyto' . $domain => ['test+replyto' . $domain, '']],
            $this->Mail->getReplyToAddresses(),
            'Bad "reply-to" addresses'
        );
    }

    /**
     * Tests removal of duplicate recipients and reply-tos.
     */
    public function testDuplicateIDNRemoved()
    {
        if (!PHPMailer::idnSupported()) {
            self::markTestSkipped('intl and/or mbstring extensions are not available');
        }

        $this->Mail->clearAllRecipients();
        $this->Mail->clearReplyTos();

        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;

        self::assertTrue($this->Mail->addAddress('test@françois.ch'));
        self::assertFalse($this->Mail->addAddress('test@françois.ch'));
        self::assertTrue($this->Mail->addAddress('test@FRANÇOIS.CH'));
        self::assertFalse($this->Mail->addAddress('test@FRANÇOIS.CH'));
        self::assertTrue($this->Mail->addAddress('test@xn--franois-xxa.ch'));
        self::assertFalse($this->Mail->addAddress('test@xn--franois-xxa.ch'));
        self::assertFalse($this->Mail->addAddress('test@XN--FRANOIS-XXA.CH'));

        self::assertTrue($this->Mail->addReplyTo('test+replyto@françois.ch'));
        self::assertFalse($this->Mail->addReplyTo('test+replyto@françois.ch'));
        self::assertTrue($this->Mail->addReplyTo('test+replyto@FRANÇOIS.CH'));
        self::assertFalse($this->Mail->addReplyTo('test+replyto@FRANÇOIS.CH'));
        self::assertTrue($this->Mail->addReplyTo('test+replyto@xn--franois-xxa.ch'));
        self::assertFalse($this->Mail->addReplyTo('test+replyto@xn--franois-xxa.ch'));
        self::assertFalse($this->Mail->addReplyTo('test+replyto@XN--FRANOIS-XXA.CH'));

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //There should be only one "To" address and one "Reply-To" address.
        self::assertCount(
            1,
            $this->Mail->getToAddresses(),
            'Bad count of "to" recipients'
        );
        self::assertCount(
            1,
            $this->Mail->getReplyToAddresses(),
            'Bad count of "reply-to" addresses'
        );
    }

    /**
     * Use a fake POP3 server to test POP-before-SMTP auth with a known-good login.
     *
     * @group pop3
     */
    public function testPopBeforeSmtpGood()
    {
        //Start a fake POP server
        $pid = shell_exec(
            '/usr/bin/nohup ' .
            $this->INCLUDE_DIR .
            '/test/runfakepopserver.sh 1100 >/dev/null 2>/dev/null & printf "%u" $!'
        );
        $this->pids[] = $pid;

        sleep(1);
        //Test a known-good login
        self::assertTrue(
            POP3::popBeforeSmtp('localhost', 1100, 10, 'user', 'test', $this->Mail->SMTPDebug),
            'POP before SMTP failed'
        );
        //Kill the fake server, don't care if it fails
        @shell_exec('kill -TERM ' . escapeshellarg($pid));
        sleep(2);
    }

    /**
     * Use a fake POP3 server to test POP-before-SMTP auth
     * with a known-bad login.
     *
     * @group pop3
     */
    public function testPopBeforeSmtpBad()
    {
        //Start a fake POP server on a different port
        //so we don't inadvertently connect to the previous instance
        $pid = shell_exec(
            '/usr/bin/nohup ' .
            $this->INCLUDE_DIR .
            '/test/runfakepopserver.sh 1101 >/dev/null 2>/dev/null & printf "%u" $!'
        );
        $this->pids[] = $pid;

        sleep(2);
        //Test a known-bad login
        self::assertFalse(
            POP3::popBeforeSmtp('localhost', 1101, 10, 'user', 'xxx', $this->Mail->SMTPDebug),
            'POP before SMTP should have failed'
        );
        //Kill the fake server, don't care if it fails
        @shell_exec('kill -TERM ' . escapeshellarg($pid));
        sleep(2);
    }

    /**
     * Test SMTP host connections.
     * This test can take a long time, so run it last.
     *
     * @group slow
     */
    public function testSmtpConnect()
    {
        $this->Mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL; //Show connection-level errors
        self::assertTrue($this->Mail->smtpConnect(), 'SMTP single connect failed');
        $this->Mail->smtpClose();

        //$this->Mail->Host = 'localhost:12345;10.10.10.10:54321;' . $_REQUEST['mail_host'];
        //self::assertTrue($this->Mail->smtpConnect(), 'SMTP multi-connect failed');
        //$this->Mail->smtpClose();
        //$this->Mail->Host = '[::1]:' . $this->Mail->Port . ';' . $_REQUEST['mail_host'];
        //self::assertTrue($this->Mail->smtpConnect(), 'SMTP IPv6 literal multi-connect failed');
        //$this->Mail->smtpClose();

        //All these hosts are expected to fail
        //$this->Mail->Host = 'xyz://bogus:25;tls://[bogus]:25;ssl://localhost:12345;
        //tls://localhost:587;10.10.10.10:54321;localhost:12345;10.10.10.10'. $_REQUEST['mail_host'].' ';
        //self::assertFalse($this->Mail->smtpConnect());
        //$this->Mail->smtpClose();

        $this->Mail->Host = ' localhost:12345 ; ' . $_REQUEST['mail_host'] . ' ';
        self::assertTrue($this->Mail->smtpConnect(), 'SMTP hosts with stray spaces failed');
        $this->Mail->smtpClose();

        //Need to pick a harmless option so as not cause problems of its own! socket:bind doesn't work with Travis-CI
        $this->Mail->Host = $_REQUEST['mail_host'];
        self::assertTrue($this->Mail->smtpConnect(['ssl' => ['verify_depth' => 10]]));

        $this->Smtp = $this->Mail->getSMTPInstance();
        self::assertInstanceOf(\get_class($this->Smtp), $this->Mail->setSMTPInstance($this->Smtp));
        self::assertFalse($this->Smtp->startTLS(), 'SMTP connect with options failed');
        self::assertFalse($this->Mail->SMTPAuth);
        $this->Mail->smtpClose();
    }

    /**
     * Test OAuth method
     */
    public function testOAuth()
    {
        $PHPMailer = new PHPMailer();
        $reflection = new \ReflectionClass($PHPMailer);
        $property = $reflection->getProperty('oauth');
        $property->setAccessible(true);
        $property->setValue($PHPMailer, true);
        self::assertTrue($PHPMailer->getOAuth());

        $options = [
            'provider' => 'dummyprovider',
            'userName' => 'dummyusername',
            'clientSecret' => 'dummyclientsecret',
            'clientId' => 'dummyclientid',
            'refreshToken' => 'dummyrefreshtoken',
        ];

        $oauth = new OAuth($options);
        self::assertInstanceOf(OAuth::class, $oauth);
        $subject = $PHPMailer->setOAuth($oauth);
        self::assertNull($subject);
        self::assertInstanceOf(OAuth::class, $PHPMailer->getOAuth());
    }

    /**
     * Test ICal method
     */
    public function testICalMethod()
    {
        $this->Mail->Subject .= ': ICal method';
        $this->Mail->Body = '<h3>ICal method test.</h3>';
        $this->Mail->AltBody = 'ICal method test.';
        $this->Mail->Ical = 'BEGIN:VCALENDAR'
            . "\r\nVERSION:2.0"
            . "\r\nPRODID:-//PHPMailer//PHPMailer Calendar Plugin 1.0//EN"
            . "\r\nMETHOD:CANCEL"
            . "\r\nCALSCALE:GREGORIAN"
            . "\r\nX-MICROSOFT-CALSCALE:GREGORIAN"
            . "\r\nBEGIN:VEVENT"
            . "\r\nUID:201909250755-42825@test"
            . "\r\nDTSTART;20190930T080000Z"
            . "\r\nSEQUENCE:2"
            . "\r\nTRANSP:OPAQUE"
            . "\r\nSTATUS:CONFIRMED"
            . "\r\nDTEND:20190930T084500Z"
            . "\r\nLOCATION:[London] London Eye"
            . "\r\nSUMMARY:Test ICal method"
            . "\r\nATTENDEE;CN=Attendee, Test;ROLE=OPT-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP="
            . "\r\n TRUE:MAILTO:attendee-test@example.com"
            . "\r\nCLASS:PUBLIC"
            . "\r\nDESCRIPTION:Some plain text"
            . "\r\nORGANIZER;CN=\"Example, Test\":MAILTO:test@example.com"
            . "\r\nDTSTAMP:20190925T075546Z"
            . "\r\nCREATED:20190925T075709Z"
            . "\r\nLAST-MODIFIED:20190925T075546Z"
            . "\r\nEND:VEVENT"
            . "\r\nEND:VCALENDAR";
        $this->buildBody();
        $this->Mail->preSend();
        self::assertMatchesRegularExpression(
            '/Content-Type: text\/calendar; method=CANCEL;/',
            $this->Mail->getSentMIMEMessage(),
            'Wrong ICal method in Content-Type header'
        );
    }

    /**
     * Test ICal missing method to use default (REQUEST)
     */
    public function testICalInvalidMethod()
    {
        $this->Mail->Subject .= ': ICal method';
        $this->Mail->Body = '<h3>ICal method test.</h3>';
        $this->Mail->AltBody = 'ICal method test.';
        $this->Mail->Ical = 'BEGIN:VCALENDAR'
            . "\r\nVERSION:2.0"
            . "\r\nPRODID:-//PHPMailer//PHPMailer Calendar Plugin 1.0//EN"
            . "\r\nMETHOD:INVALID"
            . "\r\nCALSCALE:GREGORIAN"
            . "\r\nX-MICROSOFT-CALSCALE:GREGORIAN"
            . "\r\nBEGIN:VEVENT"
            . "\r\nUID:201909250755-42825@test"
            . "\r\nDTSTART;20190930T080000Z"
            . "\r\nSEQUENCE:2"
            . "\r\nTRANSP:OPAQUE"
            . "\r\nSTATUS:CONFIRMED"
            . "\r\nDTEND:20190930T084500Z"
            . "\r\nLOCATION:[London] London Eye"
            . "\r\nSUMMARY:Test ICal method"
            . "\r\nATTENDEE;CN=Attendee, Test;ROLE=OPT-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP="
            . "\r\n TRUE:MAILTO:attendee-test@example.com"
            . "\r\nCLASS:PUBLIC"
            . "\r\nDESCRIPTION:Some plain text"
            . "\r\nORGANIZER;CN=\"Example, Test\":MAILTO:test@example.com"
            . "\r\nDTSTAMP:20190925T075546Z"
            . "\r\nCREATED:20190925T075709Z"
            . "\r\nLAST-MODIFIED:20190925T075546Z"
            . "\r\nEND:VEVENT"
            . "\r\nEND:VCALENDAR";
        $this->buildBody();
        $this->Mail->preSend();
        self::assertMatchesRegularExpression(
            '/Content-Type: text\/calendar; method=REQUEST;/',
            $this->Mail->getSentMIMEMessage(),
            'Wrong ICal method in Content-Type header'
        );
    }

    /**
     * Test ICal invalid method to use default (REQUEST)
     */
    public function testICalDefaultMethod()
    {
        $this->Mail->Subject .= ': ICal method';
        $this->Mail->Body = '<h3>ICal method test.</h3>';
        $this->Mail->AltBody = 'ICal method test.';
        $this->Mail->Ical = 'BEGIN:VCALENDAR'
            . "\r\nVERSION:2.0"
            . "\r\nPRODID:-//PHPMailer//PHPMailer Calendar Plugin 1.0//EN"
            . "\r\nCALSCALE:GREGORIAN"
            . "\r\nX-MICROSOFT-CALSCALE:GREGORIAN"
            . "\r\nBEGIN:VEVENT"
            . "\r\nUID:201909250755-42825@test"
            . "\r\nDTSTART;20190930T080000Z"
            . "\r\nSEQUENCE:2"
            . "\r\nTRANSP:OPAQUE"
            . "\r\nSTATUS:CONFIRMED"
            . "\r\nDTEND:20190930T084500Z"
            . "\r\nLOCATION:[London] London Eye"
            . "\r\nSUMMARY:Test ICal method"
            . "\r\nATTENDEE;CN=Attendee, Test;ROLE=OPT-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP="
            . "\r\n TRUE:MAILTO:attendee-test@example.com"
            . "\r\nCLASS:PUBLIC"
            . "\r\nDESCRIPTION:Some plain text"
            . "\r\nORGANIZER;CN=\"Example, Test\":MAILTO:test@example.com"
            . "\r\nDTSTAMP:20190925T075546Z"
            . "\r\nCREATED:20190925T075709Z"
            . "\r\nLAST-MODIFIED:20190925T075546Z"
            . "\r\nEND:VEVENT"
            . "\r\nEND:VCALENDAR";
        $this->buildBody();
        $this->Mail->preSend();
        self::assertMatchesRegularExpression(
            '/Content-Type: text\/calendar; method=REQUEST;/',
            $this->Mail->getSentMIMEMessage(),
            'Wrong ICal method in Content-Type header'
        );
    }

    /**
     * @test
     */
    public function givenIdnAddress_addAddress_returns_true()
    {
        if (file_exists($this->INCLUDE_DIR . '/test/fakefunctions.php')) {
            include $this->INCLUDE_DIR . '/test/fakefunctions.php';
            $this->assertTrue($this->Mail->addAddress('test@françois.ch'));
        }
    }

    /**
     * @test
     */
    public function givenIdnAddress_addReplyTo_returns_true()
    {
        if (file_exists($this->INCLUDE_DIR . '/test/fakefunctions.php')) {
            include $this->INCLUDE_DIR . '/test/fakefunctions.php';
            $this->assertTrue($this->Mail->addReplyTo('test@françois.ch'));
        }
    }

    /**
     * @test
     */
    public function erroneousAddress_addAddress_returns_false()
    {
        $this->assertFalse($this->Mail->addAddress('mehome.com'));
    }

    /**
     * Test RFC822 address list parsing using PHPMailer's parser.
     * @test
     */
    public function imapParsedAddressList_parseAddress_returnsAddressArray()
    {
        $addressLine = 'joe@example.com, <me@example.com>, Joe Doe <doe@example.com>,' .
            ' "John O\'Groats" <johnog@example.net>,' .
            ' =?utf-8?B?0J3QsNC30LLQsNC90LjQtSDRgtC10YHRgtCw?= <encoded@example.org>';

        //Test using PHPMailer's own parser
        $expected = [
            [
                'name' => '',
                'address' => 'joe@example.com',
            ],
            [
                'name' => '',
                'address' => 'me@example.com',
            ],
            [
                'name' => 'Joe Doe',
                'address' => 'doe@example.com',
            ],
            [
                'name' => "John O'Groats",
                'address' => 'johnog@example.net',
            ],
            [
                'name' => 'Название теста',
                'address' => 'encoded@example.org',
            ],
        ];
        $parsed = PHPMailer::parseAddresses($addressLine, false);
        $this->assertSameSize($expected, $parsed);
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertSame($expected[$i], $parsed[$i]);
        }
    }

    /**
     * Test RFC822 address list parsing using the IMAP extension's parser.
     * @test
     */
    public function imapParsedAddressList_parseAddress_returnsAddressArray_usingImap()
    {
        if (!extension_loaded('imap')) {
            $this->markTestSkipped("imap extension missing, can't run this test");
        }
        $addressLine = 'joe@example.com, <me@example.com>, Joe Doe <doe@example.com>,' .
            ' "John O\'Groats" <johnog@example.net>,' .
            ' =?utf-8?B?0J3QsNC30LLQsNC90LjQtSDRgtC10YHRgtCw?= <encoded@example.org>';
        $expected = [
            [
                'name' => '',
                'address' => 'joe@example.com',
            ],
            [
                'name' => '',
                'address' => 'me@example.com',
            ],
            [
                'name' => 'Joe Doe',
                'address' => 'doe@example.com',
            ],
            [
                'name' => "John O'Groats",
                'address' => 'johnog@example.net',
            ],
            [
                'name' => 'Название теста',
                'address' => 'encoded@example.org',
            ],
        ];
        $parsed = PHPMailer::parseAddresses($addressLine, true);
        $this->assertSameSize($expected, $parsed);
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertSame($expected[$i], $parsed[$i]);
        }
    }

    /**
     * @test
     */
    public function givenIdnAddress_punyencodeAddress_returnsCorrectCode()
    {
        if (file_exists($this->INCLUDE_DIR . '/test/fakefunctions.php')) {
            include $this->INCLUDE_DIR . '/test/fakefunctions.php';
            //This source file is in UTF-8, so characters here are in native charset
            $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
            $result = $this->Mail->punyencodeAddress(
                html_entity_decode('test@fran&ccedil;ois.ch', ENT_COMPAT, PHPMailer::CHARSET_UTF8)
            );
            $this->assertEquals('test@xn--franois-xxa.ch', $result);
            //To force working another charset, decode an ASCII string to avoid literal string charset issues
            $this->Mail->CharSet = PHPMailer::CHARSET_ISO88591;
            $result = $this->Mail->punyencodeAddress(
                html_entity_decode('test@fran&ccedil;ois.ch', ENT_COMPAT, PHPMailer::CHARSET_ISO88591)
            );
            $this->assertEquals('test@xn--franois-xxa.ch', $result);
        }
    }

    /**
     * @test
     */
    public function veryLongWordInMessage_wrapText_returnsWrappedText()
    {
        $message = 'Lorem ipsumdolorsitametconsetetursadipscingelitrseddiamnonumy';
        $expected = 'Lorem' . PHPMailer::getLE() .
            'ipsumdolorsitametconsetetursadipscingelitrseddiamnonumy' . PHPMailer::getLE();
        $expectedqp = 'Lorem ipsumdolorsitametconsetetursadipscingelitrs=' .
            PHPMailer::getLE() . 'eddiamnonumy' . PHPMailer::getLE();
        $this->assertEquals($this->Mail->wrapText($message, 50, true), $expectedqp);
        $this->assertEquals($this->Mail->wrapText($message, 50, false), $expected);
    }

    /**
     * @test
     */
    public function encodedText_utf8CharBoundary_returnsCorrectMaxLength()
    {
        $encodedWordWithMultiByteCharFirstByte = 'H=E4tten';
        $encodedSingleByteCharacter = '=0C';
        $encodedWordWithMultiByteCharMiddletByte = 'L=C3=B6rem';

        $this->assertEquals(1, $this->Mail->utf8CharBoundary($encodedWordWithMultiByteCharFirstByte, 3));
        $this->assertEquals(3, $this->Mail->utf8CharBoundary($encodedSingleByteCharacter, 3));
        $this->assertEquals(1, $this->Mail->utf8CharBoundary($encodedWordWithMultiByteCharMiddletByte, 6));
    }
}
/*
 * This is a sample form for setting appropriate test values through a browser
 * These values can also be set using a file called testbootstrap.php (not in repo) in the same folder as this script
 * which is probably more useful if you run these tests a lot
 * <html>
 * <body>
 * <h3>PHPMailer Unit Test</h3>
 * By entering a SMTP hostname it will automatically perform tests with SMTP.
 *
 * <form name="phpmailer_unit" action=__FILE__ method="get">
 * <input type="hidden" name="submitted" value="1"/>
 * From Address: <input type="text" size="50" name="mail_from" value="<?php echo get("mail_from"); ?>"/>
 * <br/>
 * To Address: <input type="text" size="50" name="mail_to" value="<?php echo get("mail_to"); ?>"/>
 * <br/>
 * Cc Address: <input type="text" size="50" name="mail_cc" value="<?php echo get("mail_cc"); ?>"/>
 * <br/>
 * SMTP Hostname: <input type="text" size="50" name="mail_host" value="<?php echo get("mail_host"); ?>"/>
 * <p/>
 * <input type="submit" value="Run Test"/>
 *
 * </form>
 * </body>
 * </html>
 */