<?php
/*
Problem Description

We have a new version of our API available at http://api.kivaws.org/graphql

Recommended that you install a GraphQL extension for your respective browser for easy reading, such as ChromeiQL for Google Chrome. Then read the docs to understand some of the different pieces of data available to work with.

With this information create a script that queries the GraphQL API for loans. Filter for loans that have a status of fundRaising and a plannedExpirationDate in the next 24 hours. Use the loan amount property on those loans to determine the total dollar amount it would take to fund all of these loans and return or display that as a result. Show also a link to each loan and the amount it has left to fundraise.

Bonus: Write a test that mocks Kiva's API response and proves out that your code does the right calculation and returns the right values.

 

*/




error_reporting(E_ALL);
function KivaTest($testMode = false){
  /*So, this is basically the search we need. expiringSoon is not optional here: it takes the returned data down to ~300 from ~6000 (which causes a data throttling issue), and it was the only query filter I could find. setting the expiring_soon_window to 24 hours would work better but I couldn't figure out how to do that.  */
  $query = '{
    loans(filters: {status: fundRaising, expiringSoon: true}, sortBy: expiringSoon, limit: 5000) {
      totalCount
      values {      
        id
        plannedExpirationDate
        loanAmount
        fundedAmount
      }
    }
  }';
  $query = urlencode($query);  
  
  if ($testMode){ 
    //to run the test accurately, the time has to be set back.
    //to see it in the current time against the data, use $now = time();
    $now = 1502610220; 
    
    //this testdata was grabbed by a random selection from the site.
    //when this runs, 3 loans are in the window, unfunded for $700.
    $file = '{
      "data": {
        "loans": {
          "totalCount": 20,
          "values": [
            {
              "id": 1329651,
              "plannedExpirationDate": "2017-08-16T18:10:08Z",
              "loanAmount": "3475.00",
              "fundedAmount": "950.00"
            },
            {
              "id": 1335745,
              "plannedExpirationDate": "2017-08-14T18:40:04Z",
              "loanAmount": "700.00",
              "fundedAmount": "175.00"
            },
            {
              "id": 1337264,
              "plannedExpirationDate": "2017-08-16T12:20:03Z",
              "loanAmount": "1725.00",
              "fundedAmount": "50.00"
            },
            {
              "id": 1339414,
              "plannedExpirationDate": "2017-08-15T20:00:04Z",
              "loanAmount": "1600.00",
              "fundedAmount": "0.00"
            },
            {
              "id": 1339476,
              "plannedExpirationDate": "2017-08-18T02:50:06Z",
              "loanAmount": "500.00",
              "fundedAmount": "50.00"
            },
            {
              "id": 1329517,
              "plannedExpirationDate": "2017-08-16T22:20:04Z",
              "loanAmount": "300.00",
              "fundedAmount": "0.00"
            },
            {
              "id": 1337278,
              "plannedExpirationDate": "2017-08-16T12:30:04Z",
              "loanAmount": "300.00",
              "fundedAmount": "0.00"
            },
            {
              "id": 1322678,
              "plannedExpirationDate": "2017-08-17T08:00:02Z",
              "loanAmount": "800.00",
              "fundedAmount": "125.00"
            },
            {
              "id": 1334850,
              "plannedExpirationDate": "2017-08-14T04:40:03Z",
              "loanAmount": "200.00",
              "fundedAmount": "25.00"
            },
            {
              "id": 1338439,
              "plannedExpirationDate": "2017-08-13T14:50:04Z",
              "loanAmount": "350.00",
              "fundedAmount": "200.00"
            },
            {
              "id": 1335818,
              "plannedExpirationDate": "2017-08-14T20:20:02Z",
              "loanAmount": "2925.00",
              "fundedAmount": "2400.00"
            },
            {
              "id": 1338604,
              "plannedExpirationDate": "2017-08-14T12:20:02Z",
              "loanAmount": "6225.00",
              "fundedAmount": "2550.00"
            },
            {
              "id": 1337031,
              "plannedExpirationDate": "2017-08-16T04:40:02Z",
              "loanAmount": "550.00",
              "fundedAmount": "200.00"
            },
            {
              "id": 1317872,
              "plannedExpirationDate": "2017-08-17T01:20:05Z",
              "loanAmount": "2500.00",
              "fundedAmount": "75.00"
            },
            {
              "id": 1337336,
              "plannedExpirationDate": "2017-08-16T13:30:03Z",
              "loanAmount": "850.00",
              "fundedAmount": "150.00"
            },
            {
              "id": 1335971,
              "plannedExpirationDate": "2017-08-16T01:50:03Z",
              "loanAmount": "900.00",
              "fundedAmount": "300.00"
            },
            {
              "id": 1338612,
              "plannedExpirationDate": "2017-08-14T12:10:03Z",
              "loanAmount": "500.00",
              "fundedAmount": "175.00"
            },
            {
              "id": 1334849,
              "plannedExpirationDate": "2017-08-13T13:50:05Z",
              "loanAmount": "2000.00",
              "fundedAmount": "1625.00"
            },
            {
              "id": 1337251,
              "plannedExpirationDate": "2017-08-16T12:10:04Z",
              "loanAmount": "500.00",
              "fundedAmount": "0.00"
            },
            {
              "id": 1336699,
              "plannedExpirationDate": "2017-08-15T16:00:03Z",
              "loanAmount": "675.00",
              "fundedAmount": "25.00"
            }
          ]
        }
      }
    }';
  }//end if testmode
  else{  
    //not in test mode, use the real "now"
    $now = time();
    /*get data from the api. this query string will exclude all loans already expired.
    returns up to 5000 entries; when tested there were only 300 results to parse.*/
    $file = file_get_contents("http://api.kivaws.org/graphql?query=".$query);
  }
    $result = json_decode($file); 
     
    
    //just add up all the loans without regard for any filter.
    $totalLoanAmount = 0;
    $loansAlreadyExpired = 0;
    $loansOutsideWindow = 0;
    $loansTotalled = 0;
    foreach ($result->data->loans->values as $loan)
    {    
      /*here the first line excludes any loan that will expire some time later than the 24 hour window */
      
      if(strtotime($loan->plannedExpirationDate) < $now+(24*60*60)){      
        $unfunded = (float)$loan->loanAmount - (float)$loan->fundedAmount;
        echo 'Loan ID: <a href="http://www.kiva.org/lend/'.$loan->id.'">'. $loan->id . '</a> Left to Fund: $'. $unfunded .'<br>';
        $totalLoanAmount += $unfunded;
        $loansTotalled++;
      }
      else
      {
        //data gathering - how many loans are being excluded?    
        $loansOutsideWindow++;
      }
          
    }
  
  echo 'Total to fund all loans $'.$totalLoanAmount.'<br>';
  echo 'Loans Tabulated: '.$loansTotalled.'<br>';
  echo 'Loans Outside Window: '.$loansOutsideWindow.'<br>';
  echo 'TotalCount from result: '.$result->data->loans->totalCount.'<br>';
  
}


echo "<html><body>";
echo "Test mode:<br>";
KivaTest(true);
echo "<br>API mode:<br>";
KivaTest();
echo "</body></html>";
?>