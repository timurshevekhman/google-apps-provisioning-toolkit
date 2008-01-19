#from xml.etree import ElementTree
import gdata.apps.service, sys, getopt, csv, time, datetime, threading, Queue

# make sure that file arguments are received
try:
    opts, args = getopt.getopt(sys.argv[1:], "", ["domain=", "admin=", "password=", "file=", "action=", "threads=", "permanentdel=", "verbose="])
except getopt.error, msg:
    print msg
    print "Missing arguments. Usage:"
    print "     python domain_sync.py --domain [domain.com] --admin [admin] --password [password] --file [/path/file.csv] --action [Sync] --threads [50] --verbose [1]"
    sys.exit(2)

appsdomain = ""
appsadmin = ""
appspassword = ""
csvfile = ""
threads = 100
action = "add"
permdelete = 0
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
    elif arg == "--action":
        action = arg_value
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
    elif arg == "--permanentdel":
        try:
            permdelete = int(arg_value)
        except ValueError, msg:
#           print msg
            print "Argument --permanentdel must be a 0 or 1"
            print "Usage: --permanentdel [1]"
            sys.exit(2)

if appsdomain == "" or appsadmin == "" or appspassword== "" or csvfile == "":
    print "Missing arguments. Usage:"
    print "     python domain_sync.py --domain [domain.com] --admin [admin] --password [password] --file [/path/file.csv] --threads [50]"
    sys.exit(2)

#Function to generate users array
def UsersArray(feed):
    if(len(feed.entry) == 0):
        print 'No entries in feed.\n'
    for i, entry in enumerate(feed.entry):
        usernames_array.append(entry.title.text)
        account = [entry.title.text, entry.name.given_name, entry.name.family_name]
        users_array.append(account)

# multithreading class
class MyThread(threading.Thread):
    def __init__(self, username, fname, lname, password):
        threading.Thread.__init__(self)
        # grab variables
        self.username = username
        self.fname = fname
        self.lname = lname
        self.password = password

    def run(self):
        try:
            username_index = usernames_array.index(self.username)
            users_array[username_index][0] = "skip"
#            print self.username, ",", self.fname, ",", self.lname, ",", self.password, ",", "Update"
            writer_sync.writerow([self.username, self.fname, self.lname, self.password, "Update"])
        except ValueError, msg:
#            print self.username, ",", self.fname, ",", self.lname, ",", self.password, ",", "Create"
            writer_sync.writerow([self.username, self.fname, self.lname, self.password, "Create"])

if __name__ == '__main__':
    if verbose == 1:
        print "--- Start", datetime.datetime.now(), "Start ---"
        print "Reading file:", csvfile

    # read csv file
    try:
        reader = csv.reader(open(csvfile, "rb"))
        header = reader.next()
    except IOError, msg:
        print msg
        sys.exit(2)

    csplit = csvfile.split(".")
    csvfile_sync = ""
    for i in range(len(csplit)):
        if i > 0:
            csvfile_sync += "." + csplit[i]
        else:
            csvfile_sync += csplit[i]
        if i == len(csplit)-2:
            csvfile_sync += "_batch"

    # create sync file
    writer_sync = csv.writer(open(csvfile_sync, "wb"))
    header_new = header
    header_new.append("action")
    writer_sync.writerow(header_new)

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
        if action == "add":
            writer_sync.writerow([username, fname, lname, password, "Create"])
        elif action == "delete":
            if permdelete == 1:
                writer_sync.writerow([username, fname, lname, password, "Delete"])
            else:
                writer_sync.writerow([username, fname, lname, password, "Suspend"])
        elif action == "update":
            writer_sync.writerow([username, fname ,lname, password, "Update"])
        elif action == "addupdate" or action == "sync":
            thread = MyThread(username, fname, lname, password)
            # add thread to queue
            queue.put(thread)
        row_count += 1
    if verbose == 1:
        print row_count, "records in", csvfile
        print "Creating batch file:", csvfile_sync

    if action == "addupdate" or action == "sync":
        # start service
        appsemail = appsadmin + "@" + appsdomain
        try:
            service = gdata.apps.service.AppsService(email=appsemail, domain=appsdomain, password=appspassword)
        except ValueError, msg:
            print msg
            sys.exit(2)

        # Use programmatic login for better error management
        service.ProgrammaticLogin()

        # Get users
        if verbose == 1:
            print "Retrieving domain users.  This may take a few minutes if domain has a large number of users. -", datetime.datetime.now()

        users_feed = service.RetrieveAllUsers()

#        print "--- feed", datetime.datetime.now(), "feed ---"

        # Create users array
        users_array = []
        usernames_array = []
        UsersArray(users_feed)
        
        if verbose == 1:
            print str(len(usernames_array)), "domain users received -", datetime.datetime.now()
            print "Processing records"

        records_total = queue.qsize();

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
#           print str(threading.activeCount()-1), "threads running", datetime.datetime.now()
           # display # of records left to process
            if verbose == 1 and queue.qsize() > 0:
                print str(records_total - queue.qsize()), "records processed"
            # pause for half a second to allow threads to finish
            time.sleep(0.5)
        if action == "sync":
            for i in users_array:
                if i[0] is not "skip":
                    if permdelete == 1:
#                       print i[0], ",",i[2], ",", i[1], ",", "******", ",", "Delete"
                        writer_sync.writerow([i[0], i[2], i[1], "******", "Delete"])
                    else:
                        writer_sync.writerow([i[0], i[2], i[1], "******", "Suspend"])
        if verbose == 1:
            print "All", records_total ,"records processed"

    if verbose == 1:
        print csvfile_sync, "is ready for batch process"
        print "--- end", datetime.datetime.now(), "end ---"
    print "OK"