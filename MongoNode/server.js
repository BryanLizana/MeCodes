const express = require('express');
const bodyParser= require('body-parser')
const app = express();
const MongoClient = require('mongodb').MongoClient

app.set('view engine', 'ejs')
app.use(bodyParser.urlencoded({extended: true}))


app.get('/', (req, res) => {
    db.collection('quotes').find().toArray((err, result) => {
      if (err) return console.log(err)
      // renders index.ejs
      res.render('index.ejs', {quotes: result})
    })
  })


app.get('/list', function(req, res) {
    // res.send('List')
    res.sendFile(__dirname + '/pages/index.html')
})

app.post('/quotes', (req, res) => {
    db.collection('quotes').insertOne(req.body, (err, result) => {
        if (err) return console.log(err)

        console.log('saved to database')
        res.redirect('/')
    })
})

// MongoClient.connect('mongodb://blizana:delao123456@ds123603.mlab.com:23603/test_one', { useNewUrlParser: true } , (err, database) => {
MongoClient.connect('mongodb://localhost:27017/test_one', { useNewUrlParser: true } , (err, database) => {
    if (err) return console.log(err)
    db = database.db('test_one') // whatever your database name is
    app.listen(3030, () => {
        console.log('listening on 3030')
    })
})