#from xml.etree import ElementTree
import gdata.apps.service, sys, getopt, csv, time, datetime, threading, random, Queue

# make sure that file arguments are received
# e.g. batch_adduser.py path/filename.csv
try:
    opts, args = getopt.getopt(sys.argv[1:], "", ["domain=", "admin=", "password=", "file=", "threads=", "verbose="])
except getopt.error, msg:
    print msg
    print "Missing arguments. Usage:"
    print "     python batch_adduser.py --domain [domain.com] --admin [admin] --password [password] --file [/path/file.csv] --threads [50] --verbose [1]"
    sys.exit(2)

appsdomain = ""
appsadmin = ""
appspassword = ""
csvfile = ""
threads = 100
deletion = 0
verbose = 0


# Process arguments
for arg, arg_value in opts:
    if arg == "--domain":
        appsdomain = arg_value
    elif arg == "--admin":
        appsadmin = arg_value
    elif arg == "--password":
        appspassword = arg_value
    elif arg == "--file":
        csvfile = arg_value
    elif arg == "--threads":
        threads = arg_value
    elif arg == "--verbose":
        try:
            verbose = arg_value
        except ValueError, msg:
#           print msg
            print "Argument --verbose must be a 0 or 1"
            print "Usage: --verbose [1]"
            sys.exit(2)

if appsdomain == "" or appsadmin == "" or appspassword == "" or csvfile == "":
    print "Missing arguments. Usage:"
    print "     python batch_adduser.py --domain [domain.com] --admin [admin] --password [password] --file [/path/file.csv] --threads [50]"
    sys.exit(2)

def errormsg(error_code):
    error_msg = ""
    if error_code == 1300:
        error_msg = "- <a href=\"http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference.html#appendix_d\" target=\"_blank\">Duplicate User</a>"
    if error_code == 1303:
        error_msg = "- <a href=\"http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference.html#appendix_d\" target=\"_blank\">Invalid Username</a>"
    if error_code == 1402:
        error_msg = "- <a href=\"http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference.html#appendix_d\" target=\"_blank\">Invalid Password</a>"
    return error_msg

# multithreading class
class MyThread(threading.Thread):
    def __init__(self, username, lname, fname, password, action):
        threading.Thread.__init__(self)
        # grab variables
        self.username = username
        self.lname = lname
        self.fname = fname
        self.password = password
        self.action = action

    def run(self):
        # execute account creation request
        if self.action == "Create":
            try:
                service.CreateUser(self.username, self.lname, self.fname, self.password, suspended="false")
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", "OK", ",", datetime.datetime.now()
                writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, "OK", datetime.datetime.now()])
            # grab exeption on error
            except gdata.apps.service.AppsForYourDomainException, e:
                error_msg = errormsg(e.error_code)
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", e.error_code, error_msg, ",", datetime.datetime.now()
                writer_log.writerow([self.username,self.fname, self.lname,  self.password, self.action, e.error_code, datetime.datetime.now()])
                writer_errors.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])
        if self.action == "Update":
            try:
                u_user = service.RetrieveUser(self.username)
                u_user.name.given_name = self.fname
                u_user.name.family_name = self.lname
                u_user.login.password = self.password
                try:
                    service.UpdateUser(self.username, u_user)
#                    print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", "OK", ",", datetime.datetime.now()
                    writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, "OK", datetime.datetime.now()])
                # grab exeption on error
                except gdata.apps.service.AppsForYourDomainException, e:
                    error_msg = errormsg(e.error_code)
#                    print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", e.error_code, error_msg, ",", datetime.datetime.now()
                    writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])
                    writer_errors.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])
            except gdata.apps.service.AppsForYourDomainException, e:
                error_msg = errormsg(e.error_code)
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", e.error_code, error_msg, ",", datetime.datetime.now()
                writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])
                writer_errors.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])

        if self.action == "Delete":
            try:
                service.DeleteUser(self.username)
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", "OK", ",", datetime.datetime.now()
                writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, "OK", datetime.datetime.now()])
            # grab exeption on error
            except gdata.apps.service.AppsForYourDomainException, e:
                error_msg = errormsg(e.error_code)
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", e.error_code, error_msg, ",", datetime.datetime.now()
                writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])
                writer_errors.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])

        if self.action == "Suspend":
            try:
                service.SuspendUser(self.username)
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", "OK", ",", datetime.datetime.now()
                writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, "OK", datetime.datetime.now()])
            # grab exeption on error
            except gdata.apps.service.AppsForYourDomainException, e:
                error_msg = errormsg(e.error_code)
#                print self.username, ",", self.lname, ",", self.fname, ",", self.password, ",", self.action, ",", e.error_code, error_msg, ",", datetime.datetime.now()
                writer_log.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])
                writer_errors.writerow([self.username, self.fname, self.lname, self.password, self.action, e.error_code, datetime.datetime.now()])


if __name__ == '__main__':

    if verbose == 1:
        print "--- Start", datetime.datetime.now(), "Start ---"
        print "Reading file:", csvfile
        
    # read csv file
    reader = csv.reader(open(csvfile, "rb"))
    header = reader.next()


    csplit = csvfile.split(".")
    csvfile_log = ""
    csvfile_errors = ""
    for i in range(len(csplit)):
        if i > 0:
            csvfile_log += "." + csplit[i]
            csvfile_errors += "." + csplit[i]
        else:
            csvfile_log += csplit[i]
            csvfile_errors += csplit[i]
        if i == len(csplit)-2:
            csvfile_log += "_log"
            csvfile_errors += "_errors"

    # create log file
    csvfile_log_handle = open(csvfile_log, "wb")
    writer_log = csv.writer(csvfile_log_handle)
    header_new = header
    header_new.append("result")
    header_new.append("time")
    writer_log.writerow(header_new)


    # create error file
    csvfile_errors_handle = open(csvfile_errors, "wb")
    writer_errors = csv.writer(csvfile_errors_handle)
    writer_errors.writerow(header_new)


    # start service
    appsemail = appsadmin + "@" + appsdomain

    try:
        service = gdata.apps.service.AppsService(email=appsemail, domain=appsdomain, password=appspassword)
    except ValueError, msg:
        print msg
        sys.exit(2)

    # Use programmatic login for better error management
    service.ProgrammaticLogin()

#    print "--- start", datetime.datetime.now(), "start ---"

    # Set maximum number of continuous threads
    try:
        threads = int(threads)
    except ValueError, msg:
#        print msg
        print "Argument --threads must be a number"
        print "Usage: --threads [50]"
        sys.exit(2)
    load = threads

    #create queue
    queue = Queue.Queue()
    
    row_count = 0

    # read rows
    for row in reader:
        # set the required vars for account creation
        username = row[0].strip()
        fname = row[1].strip()
        lname = row[2].strip()
        password = row[3].strip()
        action = row[4].strip()
        thread = MyThread(username, lname, fname, password, action)
        # add thread to queue
        queue.put(thread)
        row_count += 1

    if verbose == 1:
        print row_count, "records in", csvfile

    # loop through queue and start threads not to exceed maximum
    while threading.activeCount() > 1 or queue.qsize > 0:
        # add threads if thread count is less than maximum
        if threading.activeCount() < load:
            if queue.qsize() >= load:
                threads = load - threading.activeCount()
            else:
                threads = queue.qsize()
            if threads > 0:
                for i in range(threads):
                    activethread = queue.get()
                    activethread.start()
            else:
                # exit loop if queue is empty and only main thread is active
                if threading.activeCount() <= 1:
                    break
#        print str(threading.activeCount()-1), "requests running"

        if verbose == 1 and queue.qsize() > 0:
                print str(row_count - queue.qsize()), "records processed"
        # pause for half a second to allow threads to finish
        time.sleep(0.5)

    if verbose == 1:
        print "All", row_count ,"records processed"
        print "--- end", datetime.datetime.now(), "end ---"
    print "OK"