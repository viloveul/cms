class Hello {
  exec (params, args) {
    console.log(params, args)
  }
}

module.exports = new Hello()
