import React from 'react';
import { StyleSheet, Dimensions, TouchableOpacity, ScrollView, ActivityIndicator } from 'react-native';
import { Block } from 'galio-framework';
import { Icon } from 'react-native-elements';
import { Header } from '../components/';
import AlphabetCircle from '../components/AlphabetCircle';
import DiseaseButton from '../components/DiseaseButton';
import NoItem from '../components/NoItem';

const { width, height } = Dimensions.get('screen');

export default class Home extends React.Component {
  constructor(props) {
    super(props)
    this.state = { 
      alphabet: [['أ'],['ب'],['ت'],['ث'],['ج'],['ح'],['خ'],['د'],['ذ'],['ر'],['ز'],['س'],['ش'],['ص'],['ض'],['ط'],['ظ'],['ع'],['غ'],['ف'],['ق'],['ك'],['ل'],['م'],['ن'],['ه'],['و'],['ي']],
      // alphabet: [ ['A'],  ['B'],  ['C'],  ['D'],  ['E'],  ['F'],  ['G'],  ['H'],  ['I'],  ['J'],  ['K'],  ['L'],  ['M'],  ['N'],  ['O'],  ['P'],  ['Q'],  ['R'],  ['S'],  ['T'],  ['U'],  ['V'],  ['W'],  ['X'],  ['Y'],  ['Z'], ],
      color: [['#4BA759'],['#1E8045'],['#D8AD40'],['#CF901E'],['#C74036'],['#9C2963'],['#5B3A7C'],['#1B75B9'],['#3f9ce4'],['#4BA759'],['#1E8045'],['#D8AD40'],['#CF901E'],['#C74036'],['#9C2963'],['#5B3A7C'],['#1B75B9'],['#3f9ce4'],['#4BA759'],['#1E8045'],['#D8AD40'],['#CF901E'],['#C74036'],['#9C2963'],['#5B3A7C'],['#1B75B9'],['#3f9ce4'],['#4BA759']],
      diseases: [],
      diseasesColor: [['#35513a'],['#284534'],['#605332'],['#5d4a28'],['#5b322f'],['#4e2b3d'],['#4e2b3d'],['#274257'],['#314e60'],['#35513a'],['#284534'],['#605332'],['#5d4a28'],['#5b322f'],['#4e2b3d'],['#4e2b3d'],['#274257'],['#314e60'],['#35513a'],['#284534'],['#605332'],['#5d4a28'],['#5b322f'],['#4e2b3d'],['#4e2b3d'],['#274257'],['#314e60'],['#35513a']],
      selectedColor:'#35513a',
      selectedAlphabet:'أ',
      // selectedAlphabet:'A',
      selectedDisease: '',
      searchbarVisible: false,
      remeberColor: '#35513a',
      name: 'search',
      distance: 0,
      isLoading: true,
      myDiseasesList: Object,
    };
}
  /* 
    AH- this function is calling the api to get the result of the search
  */
  callsearchAPI = (mySearchValue) => { 
    this.setState({
      isLoading: true,
    });
    var raw = JSON.stringify({
      "token": "9MRE9DbDRzMVwvRzA9IiwibWFjIjoiNWU2MWMxMzY2NmY4YTNhNDQxNDk2NDFkZTk5NDRkOTRlM2QxM2QyYzk0YWYyMzFmMTRhZjJlNDI1OWJhNWJkYSJ9",
      "myInput": mySearchValue
    });
    
    var requestOptions = {
      method: 'POST',
      headers: { 
          Accept: 'application/json',
          'Content-Type': 'application/json'
      },
      body: raw,
      redirect: 'follow'
    };
    
    fetch("https://aisiha.com/aisiha/mobileapi/searchResult", requestOptions)
    .then(response => response.json())
    .then(json => {
      if (json.code === 200) {
        var value = [];
        var count = Object.keys(json.data).length;
        for (let i = 0; i < count; i++) {
          value.push({
            id: json.data[i].id,
            name: json.data[i].name,
          });
        }
        // console.log(value);
        if (Object.keys(value).length == 0) {
          var emptyValue = [{"id": 0, "name": ""}]
          var diseases = emptyValue.map((myIndex, mValue) => {
            return (
              <NoItem /> 
            );
          });
        }else{
          var diseases = value.map((myIndex, mValue) => {
            return (
              <DiseaseButton 
              disease= {myIndex.name}
              style={{ backgroundColor: '#2C2C2C' }}
              onPress={() => this.onPressDisease(myIndex.id)}
            /> 
            );
          });
        }
        
        
        this.setState({
          isLoading: false,
          myDiseasesList: diseases,
        });
        
      }
    })
    .catch(error => console.log('error', error));    
  }

  /* 
    AH- this function is called to display the searchResult bu calling the api and updating the states and colors etc..
    this function will be called only when the user click on the Magnifying glass icon of the seach input and the input has a value(there is a text to search for) 
  */
  displaySearchResult = () => {
    this.callsearchAPI(this.props.route.params?.mySearchText);
    this.props.navigation.setParams({mySearchText: null});
  }

  /* 
    AH- this function is called when we click on the touchableOpacity
    I'm updating the header based on the value of the state searchbarVisible (to hide or display the searchBar)
  */
  onPressSearch=()=>{
    if(this.state.searchbarVisible == false){
      this.setState({
        searchbarVisible: true,
        name: 'question',
        distance: width * 0.015,
        rememberColor: this.state.selectedColor,
        rememberDiseases: this.state.myDiseasesList,
        myDiseasesList: Object,
      });
      this.props.navigation.setOptions({  header: ({ navigation, scene }) => (
          <Header
            search
            options
            title='قائمة الأمراض'
            navigation={navigation}
            scene={scene}
            isSearch={true}
            isBack={false}
          />
        ),
      });
    }else{
      this.setState({
        searchbarVisible: false,
        selectedColor: this.state.rememberColor,
        name: 'search',
        distance: 0,
        myDiseasesList: this.state.rememberDiseases
      });
  
      this.props.navigation.setOptions({  header: ({ navigation, scene }) => (
        <Header
          search
          options
          title='قائمة الأمراض'
          navigation={navigation}
          scene={scene}
          isSearch={false}
          isBack={false}
        />
      ),
    });
    }
  }

  /* 
    AH- this function is called when the user click on an alphabet button
  */
  onPressAlphabet = (value) => {
    this.setState({
      isLoading: true,
    });
    this.getDiseases(value);
  }

  /* 
    AH- call api and get the list of disease based on the selected alphabet
  */
  getDiseases = (myvalue) => {
    fetch("https://aisiha.com/aisiha/mobileapi/alphabet_ar/"+ this.state.alphabet[myvalue.myIndex][0],{
    method: 'GET',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json'
      },
    })
    .then((response) => response.json())
    .then((json) => {
      if (json.code === 200) {
        var value = [];
        var count = Object.keys(json.data).length;
        for (let i = 0; i < count; i++) {
          value.push({
              id: json.data[i].id,
              name: json.data[i].name,
          });
        }

        if (Object.keys(value).length == 0) {
          var emptyValue = [{"id": 0, "name": ""}]
          var diseases = emptyValue.map((myIndex, mValue) => {
            return (
              <NoItem /> 
            );
          });
        }else{
          var diseases = value.map((myIndex, mValue) => {
            return (
              <DiseaseButton 
              disease= {myIndex.name}
              style={{ backgroundColor: this.state.diseasesColor[myvalue.myIndex][0] }}
              onPress={() => this.onPressDisease(myIndex.id)}
            /> 
            );
          });
        }

        this.setState({
          selectedColor: this.state.diseasesColor[myvalue.myIndex][0],
          selectedAlphabet: this.state.alphabet[myvalue.myIndex][0],
          myDiseasesList: diseases,
          isLoading: false,
        });
        
      }
    })
    .catch(error => console.log('error', error));
  }

  /* 
    AH- this is function is called when the user select (press) on a disease, navigate to the details page
  */
  onPressDisease = (value) => {
    
    global.id = value;
    this.props.navigation.push('details', { id: global.id })
  }

  render() {

    /* 
      AH- check if the route.params.myseachText has a value or not, is so we display the search result view by calling the function displaySearchResult
    */
    if (this.state.searchbarVisible == true && this.props.route.params?.mySearchText) {
      this.displaySearchResult();
    }

    /* 
      AH- prepare the list of alphabet and display them
    */
    let myAlphabet = this.state.alphabet.map((myValue, myIndex) => {
      var myColor = this.state.color[myIndex][0];
      return (
        <AlphabetCircle 
        alphabet= {myValue[0]}
        style={{ backgroundColor: myColor }}
        onPress={() => this.onPressAlphabet({myIndex})}
      /> 
      );
    });

    let iconName = this.state.name
    let iconDistance = this.state.distance

    return (
      <Block style={styles.container}>
        <Block style={styles.columns}>  
          {this.state.isLoading === true ?(//AH- if isLoading is true we display a spinner otherwise we display the list of diseases
              <Block style={{ flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: '#221f20' }}>
                <ActivityIndicator size="large" color={"#f9b040"} />
              </Block>
            ):(
            <Block> 
              <ScrollView>
                <Block style={styles.listView}>
                {this.state.myDiseasesList}
                </Block>
              </ScrollView> 
            </Block>
          )}
            <Block style={styles.column_right}> 
              <ScrollView>
                <Block style={styles.alphabetView}>
                  {myAlphabet}
                </Block>
              </ScrollView>
            </Block>
        </Block>        
        <Block>
          <TouchableOpacity activeOpacity={0.5} 
            onPress={this.onPressSearch} 
            style={styles.TouchableOpacityStyle} >
            <Icon
                name= {iconName}
                color={'#FFFFFF4D'}
                height= {width * 0.1}
                size= {width * 0.1}
                bottom= {iconDistance}
              />
          </TouchableOpacity>
        </Block>
      </Block>
    );
  }

  componentDidMount() {
    this.getDiseases({"myIndex": 0});
  }
}

const styles = StyleSheet.create({
  container:{
    backgroundColor: '#221f20',
    paddingTop: height * 0.024,
    paddingLeft: width * 0.033,
    paddingRight: width * 0.033,
  },
  alphabetView: {
    width: width * 0.15,
    height: '100%',
    paddingLeft: width * 0.025,
    backgroundColor: 'transparent',
  },
  listView: {
    width: width * 0.80,
    height: '100%',
    paddingRight: width * 0.025,
    backgroundColor: 'transparent',
  },
  TouchableOpacityStyle:{
    backgroundColor:'#59595b',
    borderRadius:100,
    width: width * 0.15,
    height: width * 0.15,
    justifyContent: 'center',
    position: 'absolute',
    bottom: width * 0.07,
    left: width * 0.02,
    opacity: 0.8,
  },
  columns: {
    flexDirection: 'row',
  }, 

});

