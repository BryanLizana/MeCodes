const express = require('express');
const bodyParser= require('body-parser')
const app = express();
const MongoClient = require('mongodb').MongoClient
const router = express.Router();
const product_controller = require(__dirname + '/controllers/products.controller');

app.set('view engine', 'ejs')
app.use(bodyParser.urlencoded({extended: true}))


app.get('/', (req, res) => {
    db.collection('quotes').find().toArray((err, result) => {
      if (err) return console.log(err)
      // renders index.ejs
      res.render('index.ejs', {quotes: result})
    })
  })


app.get('/list',product_controller.list)

app.post('/quotes', (req, res) => {
    db.collection('quotes').insertOne(req.body, (err, result) => {
        if (err) return console.log(err)
        console.log('saved to database')
        res.redirect('/')
    })
})

router.put('/:id/update', product_controller.product_update);

// MongoClient.connect('mongodb://blizana:delao123456@ds123603.mlab.com:23603/test_one', { useNewUrlParser: true } , (err, database) => {
MongoClient.connect('mongodb://localhost:27017/test_one', { useNewUrlParser: true } , (err, database) => {
    if (err) return console.log(err)
    db = database.db('test_one') // whatever your database name is
    app.listen(80, () => {
        console.log('listening on 80')
    })
})
