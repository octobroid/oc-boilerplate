$.oc.module.register('cms.editor.intellisense.completer.assets', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterAssets = function (_CompleterBase) {
        babelHelpers.inherits(CompleterAssets, _CompleterBase);

        function CompleterAssets() {
            babelHelpers.classCallCheck(this, CompleterAssets);
            return babelHelpers.possibleConstructorReturn(this, (CompleterAssets.__proto__ || Object.getPrototypeOf(CompleterAssets)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterAssets, [{
            key: 'getNormalizedAssets',
            value: function getNormalizedAssets(range) {
                return this.getAssets().map(function (asset) {
                    var result = {
                        label: asset.name,
                        insertText: asset.name,
                        kind: monaco.languages.CompletionItemKind.Enum,
                        range: range,
                        detail: 'Asset'
                    };

                    return result;
                });
            }
        }, {
            key: 'provideCompletionItems',
            value: function provideCompletionItems(model, position) {
                if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                    return;
                }

                var textUntilPosition = this.intellisense.utils.textUntilPosition(model, position);
                var textAfterPosition = this.intellisense.utils.textAfterPosition(model, position);
                var wordMatches = textUntilPosition.match(/\{%\s+("|')(\w|\/|\-|\.|@)*$/);
                if (!wordMatches) {
                    return;
                }

                var wordMatchBefore = textUntilPosition.match(/("|')[\w\/\-\.@]*$/);
                if (!wordMatchBefore) {
                    return;
                }

                var wordMatchAfter = textAfterPosition.match(/[\w\/\-\.@]?("|')/);
                if (!wordMatchAfter) {
                    return;
                }

                var range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: wordMatchBefore.index + 2,
                    endColumn: position.column + wordMatchAfter[0].length - 1
                };

                return {
                    suggestions: this.getNormalizedAssets(range)
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return ['"', "'", '/', '-', '.', '@'].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterAssets;
    }(CompleterBase);

    return CompleterAssets;
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIi4uL2VzNi9jbXMuZWRpdG9yLmludGVsbGlzZW5zZS5jb21wbGV0ZXIuYXNzZXRzYW5kcGFnZXMuanMiXSwibmFtZXMiOlsiJCIsIm9jIiwibW9kdWxlIiwicmVnaXN0ZXIiLCJDb21wbGV0ZXJCYXNlIiwiaW1wb3J0IiwiQ29tcGxldGVyQXNzZXRzIiwicmFuZ2UiLCJnZXRBc3NldHMiLCJtYXAiLCJhc3NldCIsInJlc3VsdCIsImxhYmVsIiwibmFtZSIsImluc2VydFRleHQiLCJraW5kIiwibW9uYWNvIiwibGFuZ3VhZ2VzIiwiQ29tcGxldGlvbkl0ZW1LaW5kIiwiRW51bSIsImRldGFpbCIsIm1vZGVsIiwicG9zaXRpb24iLCJpbnRlbGxpc2Vuc2UiLCJtb2RlbEhhc1RhZyIsInRleHRVbnRpbFBvc2l0aW9uIiwidXRpbHMiLCJ0ZXh0QWZ0ZXJQb3NpdGlvbiIsIndvcmRNYXRjaGVzIiwibWF0Y2giLCJ3b3JkTWF0Y2hCZWZvcmUiLCJ3b3JkTWF0Y2hBZnRlciIsInN0YXJ0TGluZU51bWJlciIsImxpbmVOdW1iZXIiLCJlbmRMaW5lTnVtYmVyIiwic3RhcnRDb2x1bW4iLCJpbmRleCIsImVuZENvbHVtbiIsImNvbHVtbiIsImxlbmd0aCIsInN1Z2dlc3Rpb25zIiwiZ2V0Tm9ybWFsaXplZEFzc2V0cyIsImFscGhhTnVtQ2hhcmFjdGVycyJdLCJtYXBwaW5ncyI6IkFBQUFBLEVBQUVDLEVBQUYsQ0FBS0MsTUFBTCxDQUFZQyxRQUFaLENBQXFCLDBDQUFyQixFQUFpRSxZQUFXO0FBQ3hFOztBQUVBLFFBQU1DLGdCQUFnQkosRUFBRUMsRUFBRixDQUFLQyxNQUFMLENBQVlHLE1BQVosQ0FBbUIsd0NBQW5CLENBQXRCOztBQUh3RSxRQUtsRUMsZUFMa0U7QUFBQTs7QUFBQTtBQUFBO0FBQUE7QUFBQTs7QUFBQTtBQUFBO0FBQUEsZ0RBVWhEQyxLQVZnRCxFQVV6QztBQUN2Qix1QkFBTyxLQUFLQyxTQUFMLEdBQWlCQyxHQUFqQixDQUFxQixVQUFDQyxLQUFELEVBQVc7QUFDbkMsd0JBQUlDLFNBQVM7QUFDVEMsK0JBQU9GLE1BQU1HLElBREo7QUFFVEMsb0NBQVlKLE1BQU1HLElBRlQ7QUFHVEUsOEJBQU1DLE9BQU9DLFNBQVAsQ0FBaUJDLGtCQUFqQixDQUFvQ0MsSUFIakM7QUFJVFosK0JBQU9BLEtBSkU7QUFLVGEsZ0NBQVE7QUFMQyxxQkFBYjs7QUFRQSwyQkFBT1QsTUFBUDtBQUNILGlCQVZNLENBQVA7QUFXSDtBQXRCbUU7QUFBQTtBQUFBLG1EQXdCN0NVLEtBeEI2QyxFQXdCdENDLFFBeEJzQyxFQXdCNUI7QUFDcEMsb0JBQUksQ0FBQyxLQUFLQyxZQUFMLENBQWtCQyxXQUFsQixDQUE4QkgsS0FBOUIsRUFBcUMsWUFBckMsQ0FBTCxFQUF5RDtBQUNyRDtBQUNIOztBQUVELG9CQUFNSSxvQkFBb0IsS0FBS0YsWUFBTCxDQUFrQkcsS0FBbEIsQ0FBd0JELGlCQUF4QixDQUEwQ0osS0FBMUMsRUFBaURDLFFBQWpELENBQTFCO0FBQ0Esb0JBQU1LLG9CQUFvQixLQUFLSixZQUFMLENBQWtCRyxLQUFsQixDQUF3QkMsaUJBQXhCLENBQTBDTixLQUExQyxFQUFpREMsUUFBakQsQ0FBMUI7QUFDQSxvQkFBTU0sY0FBY0gsa0JBQWtCSSxLQUFsQixDQUF3Qiw4QkFBeEIsQ0FBcEI7QUFDQSxvQkFBSSxDQUFDRCxXQUFMLEVBQWtCO0FBQ2Q7QUFDSDs7QUFFRCxvQkFBTUUsa0JBQWtCTCxrQkFBa0JJLEtBQWxCLENBQXdCLG9CQUF4QixDQUF4QjtBQUNBLG9CQUFJLENBQUNDLGVBQUwsRUFBc0I7QUFDbEI7QUFDSDs7QUFFRCxvQkFBTUMsaUJBQWlCSixrQkFBa0JFLEtBQWxCLENBQXdCLG1CQUF4QixDQUF2QjtBQUNBLG9CQUFJLENBQUNFLGNBQUwsRUFBcUI7QUFDakI7QUFDSDs7QUFFRCxvQkFBTXhCLFFBQVE7QUFDVnlCLHFDQUFpQlYsU0FBU1csVUFEaEI7QUFFVkMsbUNBQWVaLFNBQVNXLFVBRmQ7QUFHVkUsaUNBQWFMLGdCQUFnQk0sS0FBaEIsR0FBd0IsQ0FIM0I7QUFJVkMsK0JBQVdmLFNBQVNnQixNQUFULEdBQWtCUCxlQUFlLENBQWYsRUFBa0JRLE1BQXBDLEdBQTZDO0FBSjlDLGlCQUFkOztBQU9BLHVCQUFPO0FBQ0hDLGlDQUFhLEtBQUtDLG1CQUFMLENBQXlCbEMsS0FBekI7QUFEVixpQkFBUDtBQUdIO0FBeERtRTtBQUFBO0FBQUEsZ0NBTTVDO0FBQ3BCLHVCQUFXLENBQUMsR0FBRCxFQUFNLEdBQU4sRUFBVyxHQUFYLEVBQWdCLEdBQWhCLEVBQXFCLEdBQXJCLEVBQTBCLEdBQTFCLENBQVgsdUNBQThDLEtBQUttQyxrQkFBbkQ7QUFDSDtBQVJtRTtBQUFBO0FBQUEsTUFLMUN0QyxhQUwwQzs7QUEyRHhFLFdBQU9FLGVBQVA7QUFDSCxDQTVERCIsImZpbGUiOiJjbXMuZWRpdG9yLmludGVsbGlzZW5zZS5jb21wbGV0ZXIuYXNzZXRzYW5kcGFnZXMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIkLm9jLm1vZHVsZS5yZWdpc3RlcignY21zLmVkaXRvci5pbnRlbGxpc2Vuc2UuY29tcGxldGVyLmFzc2V0cycsIGZ1bmN0aW9uKCkge1xuICAgICd1c2Ugc3RyaWN0JztcblxuICAgIGNvbnN0IENvbXBsZXRlckJhc2UgPSAkLm9jLm1vZHVsZS5pbXBvcnQoJ2Ntcy5lZGl0b3IuaW50ZWxsaXNlbnNlLmNvbXBsZXRlci5iYXNlJyk7XG5cbiAgICBjbGFzcyBDb21wbGV0ZXJBc3NldHMgZXh0ZW5kcyBDb21wbGV0ZXJCYXNlIHtcbiAgICAgICAgZ2V0IHRyaWdnZXJDaGFyYWN0ZXJzKCkge1xuICAgICAgICAgICAgcmV0dXJuIFsuLi5bJ1wiJywgXCInXCIsICcvJywgJy0nLCAnLicsICdAJ10sIC4uLnRoaXMuYWxwaGFOdW1DaGFyYWN0ZXJzXTtcbiAgICAgICAgfVxuXG4gICAgICAgIGdldE5vcm1hbGl6ZWRBc3NldHMocmFuZ2UpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmdldEFzc2V0cygpLm1hcCgoYXNzZXQpID0+IHtcbiAgICAgICAgICAgICAgICB2YXIgcmVzdWx0ID0ge1xuICAgICAgICAgICAgICAgICAgICBsYWJlbDogYXNzZXQubmFtZSxcbiAgICAgICAgICAgICAgICAgICAgaW5zZXJ0VGV4dDogYXNzZXQubmFtZSxcbiAgICAgICAgICAgICAgICAgICAga2luZDogbW9uYWNvLmxhbmd1YWdlcy5Db21wbGV0aW9uSXRlbUtpbmQuRW51bSxcbiAgICAgICAgICAgICAgICAgICAgcmFuZ2U6IHJhbmdlLFxuICAgICAgICAgICAgICAgICAgICBkZXRhaWw6ICdBc3NldCdcbiAgICAgICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG5cbiAgICAgICAgcHJvdmlkZUNvbXBsZXRpb25JdGVtcyhtb2RlbCwgcG9zaXRpb24pIHtcbiAgICAgICAgICAgIGlmICghdGhpcy5pbnRlbGxpc2Vuc2UubW9kZWxIYXNUYWcobW9kZWwsICdjbXMtbWFya3VwJykpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGNvbnN0IHRleHRVbnRpbFBvc2l0aW9uID0gdGhpcy5pbnRlbGxpc2Vuc2UudXRpbHMudGV4dFVudGlsUG9zaXRpb24obW9kZWwsIHBvc2l0aW9uKTtcbiAgICAgICAgICAgIGNvbnN0IHRleHRBZnRlclBvc2l0aW9uID0gdGhpcy5pbnRlbGxpc2Vuc2UudXRpbHMudGV4dEFmdGVyUG9zaXRpb24obW9kZWwsIHBvc2l0aW9uKTtcbiAgICAgICAgICAgIGNvbnN0IHdvcmRNYXRjaGVzID0gdGV4dFVudGlsUG9zaXRpb24ubWF0Y2goL1xceyVcXHMrKFwifCcpKFxcd3xcXC98XFwtfFxcLnxAKSokLyk7XG4gICAgICAgICAgICBpZiAoIXdvcmRNYXRjaGVzKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjb25zdCB3b3JkTWF0Y2hCZWZvcmUgPSB0ZXh0VW50aWxQb3NpdGlvbi5tYXRjaCgvKFwifCcpW1xcd1xcL1xcLVxcLkBdKiQvKTtcbiAgICAgICAgICAgIGlmICghd29yZE1hdGNoQmVmb3JlKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjb25zdCB3b3JkTWF0Y2hBZnRlciA9IHRleHRBZnRlclBvc2l0aW9uLm1hdGNoKC9bXFx3XFwvXFwtXFwuQF0/KFwifCcpLyk7XG4gICAgICAgICAgICBpZiAoIXdvcmRNYXRjaEFmdGVyKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjb25zdCByYW5nZSA9IHtcbiAgICAgICAgICAgICAgICBzdGFydExpbmVOdW1iZXI6IHBvc2l0aW9uLmxpbmVOdW1iZXIsXG4gICAgICAgICAgICAgICAgZW5kTGluZU51bWJlcjogcG9zaXRpb24ubGluZU51bWJlcixcbiAgICAgICAgICAgICAgICBzdGFydENvbHVtbjogd29yZE1hdGNoQmVmb3JlLmluZGV4ICsgMixcbiAgICAgICAgICAgICAgICBlbmRDb2x1bW46IHBvc2l0aW9uLmNvbHVtbiArIHdvcmRNYXRjaEFmdGVyWzBdLmxlbmd0aCAtIDFcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgc3VnZ2VzdGlvbnM6IHRoaXMuZ2V0Tm9ybWFsaXplZEFzc2V0cyhyYW5nZSlcbiAgICAgICAgICAgIH07XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICByZXR1cm4gQ29tcGxldGVyQXNzZXRzO1xufSk7XG4iXX0=
