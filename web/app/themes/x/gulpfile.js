const gulp = require('gulp');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const concat = require('gulp-concat');
const replace = require('gulp-replace');
const header = require('gulp-header');
const del = require('del');
const pkg = require('./package.json');

// 基础配置
const config = {
  // 头部注释
  comment: [
    '/** v<%= pkg.version %> | <%= pkg.license %> Licensed */<%= js %>',
    {pkg: pkg, js: ';'}
  ],
  // 全部模块
  modules: 'lay,laytpl,laypage,laydate,jquery,layer,util,dropdown,slider,colorpicker,element,upload,form,table,tags,treeTable,tree,transfer,carousel,rate,flow,code'
};

// 打包目标路径
const dest = './layui';

// js
const js = () => {
  let src = [
    './layui-assets/**/{layui,layui.all,'+ config.modules +'}.js'
  ];
  return gulp.src(src)
  .pipe(uglify({
    output: {
      ascii_only: true // escape Unicode characters in strings and regexps
    },
    ie: true
  }))
  .pipe(concat('layui.js', {newLine: ''}))
  .pipe(header.apply(null, config.comment))
  .pipe(gulp.dest(dest));
};

// css
const css = () => {
  let src = [
    './layui-assets/css/**/{layui,*}.css'
  ];
  return gulp.src(src)
  .pipe(cleanCSS({
    compatibility: 'ie8'
  }))
  .pipe(concat('layui.css', {newLine: ''}))
  .pipe(gulp.dest(dest));
};

// files
const files = () => {
  let src = ['./layui-assets/**/*.{eot,svg,ttf,woff,woff2,html,json,png,jpg,gif}'];
  return gulp.src(src)
  .pipe(gulp.dest(dest));
};

// clean
const clean = () => {
  return del([dest]);
};

// 默认任务
exports.default = gulp.series(clean, gulp.parallel(js, css, files));

// 复制 dist 目录到指定路径
exports.cp = gulp.series(() => del(copyDest), () => {
  const src = `${dest}/**/*`;

  // 复制 css js
  gulp.src(`${src}.{css,js}`)
  .pipe(replace(/\n\/(\*|\/)\#[\s\S]+$/, '')) // 过滤 css,js 的 map 特定注释
  .pipe(gulp.dest(copyDest));

  // 复制其他文件
  return gulp.src([
    src,
    `!${src}.{css,js,map}` // 过滤 map 文件
  ])
  .pipe(replace(/\n\/(\*|\/)\#[\s\S]+$/, '')) // 过滤 css,js 的 map 特定注释
  .pipe(gulp.dest(copyDest));
});